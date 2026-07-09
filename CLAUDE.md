# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Jira-CLI is a PHP command-line client for interacting with JIRA (issue cloning/backporting, changelog generation,
attachment download, version listing). Requires PHP >= 5.6. Built on `console-helpers/console-kit` (Symfony
Console-based CLI framework) and `chobie/jira-api-restclient` for the JIRA REST API.

## Commands

Install dependencies:

```bash
composer install
```

Run the test suite:

```bash
vendor/bin/phpunit
```

Run a single test file or method:

```bash
vendor/bin/phpunit tests/ContainerTest.php
vendor/bin/phpunit --filter testMethodName
```

Check coding standard violations (uses `aik099/coding-standard`):

```bash
vendor/bin/phpcs --standard="vendor/aik099/coding-standard/CodingStandard" src tests
```

Run the CLI locally:

```bash
bin/jira-cli <command>
```

## Architecture

- **Entry point**: `bin/jira-cli` builds a `Container` and hands it to `Application::run()`.
- **`Container`** (`src/JiraCLI/Container.php`) extends console-kit's DI container (Pimple-based). It registers
  config defaults (`jira.url`, `jira.username`, `jira.password`, `cache.provider`), and lazy service factories:
  `cache`, `jira_api`, `backportable_issue_cloner`, `changelog_issue_cloner`. Config values are read via
  `ConfigEditor` and persisted under the working directory sub-folder `.jira-cli`.
- **`Application`** (`src/JiraCLI/Application.php`) extends console-kit's base `Application` and registers all
  commands in `getDefaultCommands()`. New commands must be added there to be discoverable.
- **`AbstractCommand`** (`src/JiraCLI/Command/AbstractCommand.php`) is the base for all commands. It pulls
  `jira_api`, `cache`, and `config_editor` from the container in `prepareDependencies()`, validates issue keys
  (`PROJECT-123` format), supports Bash completion for `project_key`/`project_keys` arguments via
  `jiraApi->getProjectKeys()`, and prints API request-count statistics in verbose mode.
- **`JiraApi`** (`src/JiraCLI/JiraApi.php`) wraps `chobie\Jira\Api`, adding caching (via `Doctrine\Common\Cache`)
  and a request counter used for statistics reporting.
- **Issue cloning** (`src/JiraCLI/Issue/`): `IssueCloner` is the shared base for walking/copying issues (via
  `chobie\Jira\Issues\Walker`), handling custom field copying (e.g. "Change Log Group", "Change Log Message") and
  issue link direction (`LINK_DIRECTION_INWARD`/`LINK_DIRECTION_OUTWARD`). `BackportableIssueCloner` and
  `ChangeLogIssueCloner` extend it for the `BackportCommand` and `ChangeLogCloneCommand` respectively.
- **Commands** (`src/JiraCLI/Command/`): `BackportCommand`, `ChangeLogCloneCommand`, `DownloadAttachmentCommand`,
  `VersionsCommand` — each corresponds 1:1 with a cloner/service and is registered in `Application`.

## Code style

- Tab indentation, license header block (with `@copyright`/`@link`) at the top of every PHP file, PHPDoc blocks on
  all class members and methods — follow the existing style in neighboring files.
- Wrap code lines over 120 characters; one argument per line for multi-argument calls.
