#!/bin/sh
# Copyright 2026 Anginger AI
#
# Licensed under the BSD 3-Clause License (see LICENSE.md)
#
# Local development bootstrap: the whole app (server-rendered pages +
# Alpine.js frontend + JSON/SSE API) is served by the PHP built-in server.
#
# Set your OpenAI key first:
#   export OPENAI_API_KEY=sk-...
#   export OPENAI_MODEL=gpt-4o-mini   # optional, this is the default
set -e
export YII_ENV=dev YII_DEBUG=true

ROOT="$(cd "$(dirname "$0")" && pwd)"

sh -c "cd '$ROOT/api' && composer install && php console.php migrate --interactive=0 && cd web && php -S 127.0.0.1:1270 index.php" &

wait
