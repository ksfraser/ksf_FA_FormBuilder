# ksf_FA_FormBuilder — AGENTS_APPENDIX.md

## Status (as of 2026-11-21)

**GitHub repo**: https://github.com/ksfraser/ksf_FA_FormBuilder (pushed, main branch)

## Completed

- Module scaffold: composer.json, hooks.php, index.php, sql/, pages/, src/ (App, Controller, Service layers)
- `submit.php` — public form submission endpoint (GET returns CSRF token, POST processes submission)
- `phpunit.xml` + `tests/bootstrap.php` + `tests/Unit/FormServiceTest.php` (5 tests, all passing)
- `ksfraser/html` dependency added to standalone `ksf_formbuilder` package
- `FormRenderer` service (standalone package) — generates static HTML forms for external sites
- `tests/Unit/FormRendererTest.php` (8 tests, all passing) in `ksf_formbuilder`

## Remaining (TODO)

| Item | Details |
|------|---------|
| Integration tests | `submit.php` endpoint needs end-to-end testing (CSRF → POST → JSON response) |
| Unit test coverage | FormFieldService, FormSubmissionService, FormActionService, processors need tests |
| SQL schema verification | All 6 sql files (`ksf_formbuilder_*.sql`) need review against actual DTOs |
| FormRenderer edge cases | Empty fields, module_link field type, invalid field types |
| ProjectDocuments | BR/FR/UC/UT traceability files not yet created |
| GitHub issues | Create issues for outstanding FormBuilder gaps |

## Architecture Notes

- Form posts flow: External static site → HTML form → POST to `submit.php` → Cloudflare → FW/Gateway → NPM proxy → FA endpoint
- `submit.php` validates CSRF token against `$_SESSION['formbuilder_csrf_token']`
- Form is looked up by `form_slug`, must be `active` and `is_public`
- Submission processed via `FormProcessorService` with registered action processors (create_lead, create_debtor, create_branch, create_contact, create_invoice, create_meeting, create_ticket, send_email, webhook)
