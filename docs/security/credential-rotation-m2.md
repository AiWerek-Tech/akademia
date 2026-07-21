# Credential Rotation — Milestone 2

**Date**: 2026-07-21
**Trigger**: Development admin password was exposed in documentation committed to Git history.
**Status**: ✅ **RESOLVED**

---

## Incident Summary

During the Milestone 2 acceptance gate, a development admin account was created with a plaintext password that was subsequently documented in `docs/testing/milestone-2-browser-results.md` and committed to the repository (commit `09f13db`).

## Findings

### Working Tree Scan (`git grep`)
| Pattern | Files Found | Action |
|---------|-------------|--------|
| `[REDACTED]` | `docs/testing/milestone-2-browser-results.md` (line 16) | Redacted |
| Database credentials | None (`.env` not tracked, `env` template is commented-out) | N/A |
| Tokens / session cookies | None | N/A |

### Git History Scan (`git log -S`)
| Pattern | Commits Found | Action |
|---------|---------------|--------|
| `[REDACTED]` | `09f13db` (1 commit) | Amended via `git commit --amend` → `0a4d5ed`, then force-pushed |

### Additional Verification
| Check | Result |
|-------|--------|
| `.env` tracked? | ❌ NOT tracked (safe) |
| `env` (template) contains credentials? | ❌ All lines commented out |
| Test fixtures contain real passwords? | ❌ Tests use `session()->set('user_id')` bypass, no real passwords |
| Audit logs store passwords? | ❌ `AuditService` redacts `password_hash` as `[REDACTED]` |
| Browser docs contain credentials? | ❌ Redacted to "Input the development admin password" |
| `composer audit` | ✅ No security vulnerability advisories |
| Full PHPUnit suite | ✅ 114 tests, 282 assertions, 0 failures |

## Remediation Actions

1. **Password rotated** in development database (`wmvaa_akademia.users`) via parameterized PHP script (script deleted after execution).
2. **Documentation redacted**: Removed plaintext password from `docs/testing/milestone-2-browser-results.md`.
3. **Git history cleaned**: Amended commit `09f13db` → `0a4d5ed` to remove the credential from the commit tree.
4. **Force push** executed to update `origin/master` and dereference the old commit.
5. **Old credential considered revoked**: The previous development password is no longer valid for any account.

## Policy Going Forward

- Development passwords must NEVER appear in documentation, source code, test fixtures, or commit messages.
- Browser testing documentation must describe actions generically (e.g., "Input the development admin password") without revealing the actual value.
- The `akademia:create-admin` CLI command should be the only method for creating admin accounts.
