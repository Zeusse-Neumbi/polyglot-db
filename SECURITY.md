# Security Policy

## Supported versions

Only the latest tagged release is supported with security fixes.

## Reporting a vulnerability

Please do not open a public issue for a security problem. Use GitHub's
**Report a vulnerability** button on the repository's Security tab, or email the
maintainer at the address on the GitHub profile.

Include the affected version, reproduction steps, and impact. Do not include
real data or credentials.

## Scope

This is a local research stack. The development credentials in `docker/` and
`.env.docker.example` are intentionally non-production and are not
vulnerabilities by themselves. Relevant reports include authentication or
authorization bypasses, injection, unsafe deserialization, exposed management
interfaces, and secret leakage.
