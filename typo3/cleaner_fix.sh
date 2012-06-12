#!/bin/sh

./cli_dispatch.phpsh lowlevel_cleaner missing_files -r -v 2 -s --AUTOFIX --YES --refindex update
./cli_dispatch.phpsh lowlevel_cleaner double_files -r -v 2 -s --AUTOFIX --YES --refindex update
./cli_dispatch.phpsh lowlevel_cleaner lost_files -r -v 2 -s --AUTOFIX --YES --refindex update
./cli_dispatch.phpsh lowlevel_cleaner orphan_records -r -v 2 -s --AUTOFIX --YES
./cli_dispatch.phpsh lowlevel_cleaner versions -r -v 2 -s --AUTOFIX --YES
./cli_dispatch.phpsh lowlevel_cleaner deleted -r -v 1 -s --AUTOFIX --YES
./cli_dispatch.phpsh lowlevel_cleaner missing_relations -r -v 2 -s --AUTOFIX --YES --refindex update
./cli_dispatch.phpsh lowlevel_cleaner cleanflexform -r -v 2 -s --AUTOFIX --YES
./cli_dispatch.phpsh lowlevel_cleaner rte_images -r -v 2 -s --refindex ignore