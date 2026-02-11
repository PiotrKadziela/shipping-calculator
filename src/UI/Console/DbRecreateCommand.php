<?php

declare(strict_types=1);

namespace App\UI\Console;

use PDO;
use PDOException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(
    name: 'app:db:recreate',
    description: 'Drops and recreates the shipping database, then runs migrations and seeds'
)]
final class DbRecreateCommand extends Command
{
    public function __construct(
        private readonly KernelInterface $kernel
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('host', null, InputOption::VALUE_OPTIONAL, 'MySQL host', $this->getEnv('DB_HOST', 'mysql'))
            ->addOption('port', null, InputOption::VALUE_OPTIONAL, 'MySQL port', $this->getEnv('DB_PORT', '3306'))
            ->addOption('database', null, InputOption::VALUE_OPTIONAL, 'Database name', $this->getEnv('DB_NAME', 'shipping'))
            ->addOption('user', null, InputOption::VALUE_OPTIONAL, 'Database user', $this->getEnv('DB_USER', 'shipping'))
            ->addOption('password', null, InputOption::VALUE_OPTIONAL, 'Database password', $this->getEnv('DB_PASSWORD', 'shipping'))
            ->addOption(
                'path',
                null,
                InputOption::VALUE_OPTIONAL,
                'Path to migration/seed SQL files',
                $this->kernel->getProjectDir().'/docker/mysql/init'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $host = (string) $input->getOption('host');
        $port = (string) $input->getOption('port');
        $database = (string) $input->getOption('database');
        $user = (string) $input->getOption('user');
        $password = (string) $input->getOption('password');
        $path = (string) $input->getOption('path');

        if (!is_dir($path)) {
            $io->error(sprintf('SQL path not found: %s', $path));
            return Command::FAILURE;
        }

        $files = glob($path.'/*.sql');
        if ($files === false || count($files) === 0) {
            $io->error(sprintf('No SQL files found in: %s', $path));
            return Command::FAILURE;
        }

        sort($files, SORT_STRING);

        try {
            $serverPdo = $this->connect($host, $port, null, $user, $password);
            $serverPdo->exec(sprintf('DROP DATABASE IF EXISTS `%s`', $database));
            $serverPdo->exec(sprintf('CREATE DATABASE `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci', $database));
            $serverPdo = null;

            $pdo = $this->connect($host, $port, $database, $user, $password);

            foreach ($files as $file) {
                $sql = trim((string) file_get_contents($file));
                if ($sql === '') {
                    continue;
                }

                $io->text(sprintf('Running %s', basename($file)));
                $pdo->exec($sql);
            }

            $io->success('Database recreated and seeded.');
            return Command::SUCCESS;
        } catch (PDOException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }

    private function connect(string $host, string $port, ?string $database, string $user, string $password): PDO
    {
        $dsn = $database === null
            ? sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $host, $port)
            : sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database);

        return new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
        ]);
    }

    private function getEnv(string $name, string $default): string
    {
        $value = $_SERVER[$name] ?? $_ENV[$name] ?? getenv($name);
        if ($value === false || $value === '') {
            return $default;
        }

        return (string) $value;
    }
}
