# TimeTracker Developer Overview

This fork is a classic page-per-file PHP application. There is no front controller or routing layer; each top-level `*.php` file is a direct entry point that bootstraps the app, performs access checks, processes a form or action, and then renders a Smarty template.

## Entry Points

The common bootstrap path is [initialize.php](initialize.php#L1). It loads shared helpers, configuration, Smarty, session state, and the auth object before any page logic runs. Most pages begin with `require_once('initialize.php')` and then immediately enforce authorization.

The main public landing page is [index.php](index.php) and it redirects authenticated users to the right starting area based on role. The login flow is handled in [login.php](login.php#L1), while authenticated users land in pages such as [time.php](time.php#L1) or [reports.php](reports.php).

## Database Access Pattern

Database access is centralized through PEAR MDB2. The connection factory lives in [WEB-INF/lib/common.lib.php](WEB-INF/lib/common.lib.php#L84), where `getConnection()` lazily creates a single global MDB2 connection from `DSN` and sets associative fetch mode.

The codebase uses direct SQL strings everywhere instead of an ORM or prepared-statement layer. Typical calls are `query()` for reads and `exec()` for writes, with values escaped by `$mdb2->quote(...)`. Representative examples are [WEB-INF/lib/Auth.class.php](WEB-INF/lib/Auth.class.php#L63), [WEB-INF/lib/auth/Auth_db.class.php](WEB-INF/lib/auth/Auth_db.class.php#L18), and [WEB-INF/lib/ttUserHelper.class.php](WEB-INF/lib/ttUserHelper.class.php#L15).

Transactions exist in MDB2, but the application mostly uses one-shot SQL statements; most business helpers follow a read-validate-write pattern rather than explicit multi-step transaction blocks.

## Session Management

Session setup happens in [initialize.php](initialize.php#L69) through `session_set_cookie_params()`, a custom session cookie name, and `session_start()`. The app uses its own cookie names, `tt_PHPSESSID` and `tt_login`, to avoid colliding with other PHP applications.

Session state is small and explicit. The auth layer stores `authenticated`, `authenticated_user_id`, and `login` in `$_SESSION` in [WEB-INF/lib/Auth.class.php](WEB-INF/lib/Auth.class.php#L95). Pages also keep some UI defaults in session, such as the selected date, client, project, task, and billable state in [time.php](time.php#L1).

## Authentication

Authentication is abstracted behind [WEB-INF/lib/Auth.class.php](WEB-INF/lib/Auth.class.php#L29). The base class manages session-based auth state, while a concrete backend is loaded dynamically by `Auth::factory()`.

This fork ships with DB-backed auth in [WEB-INF/lib/auth/Auth_db.class.php](WEB-INF/lib/auth/Auth_db.class.php#L18). It authenticates against `tt_users`, supports legacy password compatibility, and has a special fallback for `admin@localhost`.

Login is a form POST in [login.php](login.php#L1): it validates input, calls `$auth->doLogin()`, optionally triggers 2FA, sets the login cookie, and redirects based on role. Most authenticated pages check access through `ttAccessAllowed()` before doing any work.

## Access Control And Menu System

Authorization checks are centralized in `ttAccessAllowed()` in [WEB-INF/lib/common.lib.php](WEB-INF/lib/common.lib.php#L540). It first ensures the user is authenticated, then runs CSRF mitigation for POSTs, applies IP restrictions, and finally checks the required right against the active user object.

The current user object also drives menu visibility. The large-screen navigation is rendered in [WEB-INF/templates/header.tpl](WEB-INF/templates/header.tpl#L45), where menu items are conditionally shown based on rights, plugins, tracking mode, and whether the user is an admin or a normal user. The small-screen menu is simpler and links to the site map and help.

## Form Processing Model

Forms are usually built in PHP with the lightweight `Form` abstraction from [WEB-INF/lib/form/Form.class.php](WEB-INF/lib/form/Form.class.php#L1) and rendered through Smarty templates. A page typically:

1. Reads request values from `$request`.
2. Validates inputs with the `ttValid*` helpers.
3. Calls a domain helper such as `ttUserHelper`, `ttTimeHelper`, or `ttGroupHelper`.
4. Redirects on success to avoid duplicate submissions.
5. Re-renders the form with `$err` messages on failure.

The user-add flow in [user_add.php](user_add.php#L1) is representative: it collects POST data, validates it, calls `ttUserHelper::insert()`, inserts custom fields if needed, and redirects to `users.php` on success. The time-entry screen in [time.php](time.php#L1) shows the same pattern with heavier pre-processing because it also manages on-behalf users, dropdown state, custom fields, and lock checks.

## SQL Execution Pattern

SQL is assembled manually throughout the app. There is no central repository layer, and there is no pervasive prepared statement usage. Reads use `$mdb2->query($sql)`, writes use `$mdb2->exec($sql)`, and user-supplied values are usually sanitized with `$mdb2->quote(...)` before being interpolated.

Most helper classes encapsulate a single domain area and contain the SQL for that area directly. Examples include [WEB-INF/lib/ttUserHelper.class.php](WEB-INF/lib/ttUserHelper.class.php#L1), [WEB-INF/lib/ttAdmin.class.php](WEB-INF/lib/ttAdmin.class.php#L1), and [WEB-INF/lib/ttUser.class.php](WEB-INF/lib/ttUser.class.php#L1).

## Practical Mental Model

The app is best understood as a set of thin controller pages backed by shared helper classes. Bootstrap happens in `initialize.php`, auth/session state comes from `Auth` and `ttUser`, permissions are checked by `ttAccessAllowed()`, and the UI is assembled with Smarty templates plus the `Form` helper. Data access is direct SQL through MDB2.

If you need to trace a feature, start at the page file, follow its helper class, then inspect the corresponding template under `WEB-INF/templates`.