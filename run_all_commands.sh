#!/bin/bash
set -e

read -p "Would you like to refresh the database? (Y/N) [Y]: " refresh_db
refresh_db=${refresh_db:-Y}
if [[ "$refresh_db" == "Y" || "$refresh_db" == "y" ]]; then
    echo "🔄 Refreshing the database..."
    php artisan migrate:fresh
    echo "✅ Database refreshed."
fi

read -p "Would you like to flush prior queued jobs? (Y/N) [Y]: " refresh_queue
refresh_queue=${refresh_queue:-Y}
if [[ "$refresh_queue" == "Y" || "$refresh_queue" == "y" ]]; then
    echo "🧹 Flushing prior queued jobs..."
    php artisan queue:flush
    echo "✅ Queued jobs flushed."
fi

echo "📂 Running parse:files..."
php artisan parse:files --output-file=docs/parse_all.json
echo "✅ parse:files completed."

echo "🔍 Running analyze:files..."
php artisan analyze:files --output-file=docs/analyze_all.json
echo "✅ analyze:files completed."

echo "⚙️ Running passes:process..."
php artisan passes:process
echo "✅ passes:process completed."

echo "🚀 Processing the jobs in async..."
php artisan queue:progress
echo "✅ Jobs processed."

echo "🌐 Starting the Artisan server & opening the UI..."
php artisan serve &

# Wait for the server to start
sleep 5

open http://localhost:8000
echo "✅ Artisan server started and UI opened at http://localhost:8000"
