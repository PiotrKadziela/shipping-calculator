#!/usr/bin/env bash
set -euo pipefail

run_case() {
  local label="$1"
  shift
  echo "--- ${label} ---"
  docker compose run --rm app php bin/console app:calculate-shipping --no-ansi "$@" \
    | awk '/Shipping Cost:|FREE SHIPPING/ {print}'
}

run_case "Base PL" --country=PL --weight=1 --value=100 --date=2024-01-15
run_case "Base DE" --country=DE --weight=1 --value=100 --date=2024-01-15
run_case "Base US" --country=US --weight=1 --value=100 --date=2024-01-15
run_case "Base FR" --country=FR --weight=1 --value=100 --date=2024-01-15

run_case "Weight 5.0kg" --country=PL --weight=5.0 --value=100 --date=2024-01-15
run_case "Weight 5.1kg" --country=PL --weight=5.1 --value=100 --date=2024-01-15
run_case "Weight 7.2kg" --country=PL --weight=7.2 --value=100 --date=2024-01-15
run_case "Weight 10.0kg" --country=PL --weight=10.0 --value=100 --date=2024-01-15

run_case "Value 399.99" --country=PL --weight=2 --value=399.99 --date=2024-01-15
run_case "Value 400.00" --country=PL --weight=2 --value=400.00 --date=2024-01-15
run_case "Value 500.00 DE" --country=DE --weight=2 --value=500.00 --date=2024-01-15
run_case "Value 500.00 US" --country=US --weight=2 --value=500.00 --date=2024-01-15

run_case "Friday PL" --country=PL --weight=2 --value=100 --date=2024-01-19
run_case "Friday PL 7.2kg" --country=PL --weight=7.2 --value=100 --date=2024-01-19
run_case "Friday PL free" --country=PL --weight=2 --value=500 --date=2024-01-19
run_case "Friday US promo" --country=US --weight=2 --value=500 --date=2024-01-19

run_case "Weight 0" --country=PL --weight=0 --value=100 --date=2024-01-15
run_case "Weight 50" --country=PL --weight=50 --value=100 --date=2024-01-15
run_case "Value 400 exact" --country=PL --weight=2 --value=400 --date=2024-01-15
