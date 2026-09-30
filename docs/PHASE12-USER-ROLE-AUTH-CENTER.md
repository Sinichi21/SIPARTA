# Phase 12 — User / Role / Authentication Center

## Account creation

Admin only inputs:
- name
- email
- optional linked personnel
- role

Admin never enters a password.

The system:
1. generates an unguessable temporary internal password,
2. marks `must_set_password=true`,
3. sends a Laravel password-reset link as activation,
4. blocks normal login until activation is complete,
5. when the user sets the password:
   - `must_set_password=false`
   - email is marked verified
   - `password_changed_at` is updated
   - old sessions are revoked

## Roles

`super-admin` permissions remain managed by the seeder/Gate override.

Other roles can be configured from the Role & Permission UI.

## Email

This flow intentionally uses email only for important activation/recovery events.

Daily login can use password + Authenticator (TOTP) without email.
