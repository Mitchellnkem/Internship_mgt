# Secure messaging architecture decision

## Decision

**Hosting choice: self-host Matrix.** Use the Matrix protocol and its maintained JavaScript client SDK for student/employer messaging. Do not write a custom encryption protocol with browser WebCrypto. The SDK implements Matrix's device/key lifecycle and Olm/Megolm ratchets; this avoids creating a bespoke protocol that would be difficult to verify and maintain.

**Identity choice: separate Matrix credentials linked to BIT accounts.** Users authenticate to BIT and Matrix independently; never copy or reuse a BIT password to create or sign in to a Matrix account. The BIT account stores only the verified Matrix user ID link. Matrix access tokens are held in the browser session/storage required by the SDK and are sent to the BIT linking/room-verification endpoints only when needed; PHP must not persist or log them. This keeps password boundaries clear and avoids building a password federation/identity service into the first release. Account provisioning and password reset remain Matrix homeserver responsibilities.

The BIT PHP application remains responsible for sign-in, application eligibility, role checks, and deciding who may start a conversation. A Matrix homeserver transports and stores encrypted events. The browser SDK performs encryption and decryption. The PHP app must not receive room plaintext or users' Matrix encryption keys. Linking and room-validation requests pass a short-lived Matrix access token through PHP to verify identity and room membership; the app must never persist or log that token. Use HTTPS for this path. A compromised PHP server or malicious JavaScript release can still attack a web client, so verified devices, code integrity, and tight deployment access remain important.

## Initial product boundary

- Start with one-to-one conversations between a student and the employer who owns the internship to which that student applied.
- Do not create rooms for arbitrary users or expose message contents to superadmins.
- Store only the link between a BIT application and a Matrix room in MySQL; keep room messages on the Matrix homeserver.
- Store the user's Matrix ID link in MySQL, but never store Matrix access tokens or encryption keys there.
- Show a device/key verification status. Warn users when a conversation includes an unverified device or a key changes.
- E2EE protects message contents. The homeserver can still observe account IDs, room membership, IP addresses, message timing, and traffic volume.
- Do not promise message recovery across devices unless a tested encrypted backup/recovery design is implemented. A lost encryption recovery key may make old messages unreadable.

## Hosting and scale

Use a private Matrix homeserver for production, with federation disabled initially if communication should remain inside BIT-InternshipHub. Start with one application instance and a PostgreSQL-backed homeserver. Add Matrix workers, Redis, and a load balancer when measurements show sync or event throughput needs horizontal scaling. Keep the PHP application's database and Matrix's database separate, and back up both independently.

HTTPS is mandatory for both the web app and Matrix endpoint. Local HTTP development should use localhost only; browsers do not provide secure cryptographic contexts to arbitrary HTTP hosts. Production secrets belong in the deployment's secret manager, not source code or JavaScript.

## Rollout sequence

1. Select the permanent Matrix `server_name` domain; provision TLS, backups, monitoring, retention policy, and an operator account. Synapse does not allow changing `server_name` later.
2. Add a backend service account or application service that can create invite-only rooms only after checking a valid BIT application and employer ownership.
3. Add a Vite build for `matrix-js-sdk`, persist SDK crypto state in browser IndexedDB, and test supported browsers and device verification/recovery flows.
4. Link accepted application conversations to rooms, invite only the student and owning employer, and restrict room access to invited members.
5. Add abuse reporting, blocking, retention/deletion handling, and operational alerts without collecting plaintext.
6. Load-test sync/event traffic and the database before adding workers or federation.

## Current implementation status

The workspace now has server-side APIs to link a Matrix identity and authorize an encrypted room for an eligible application. `MATRIX_HOMESERVER_URL` and `MATRIX_SERVER_NAME` are deployment environment settings; the endpoint rejects non-HTTPS URLs except localhost development. The SQL link tables hold Matrix IDs and room references only. A browser UI/SDK bundle, permanent server name, TLS homeserver, account enrollment, device verification/recovery UX, backups, and deployment operations are still required before users can send E2EE messages. No custom cryptography or plaintext message endpoint was added.

Matrix's current JavaScript SDK supports browser builds and E2EE via its Rust crypto implementation. Matrix E2EE uses device keys and ratchets; devices need verification and persistent crypto state. Review the protocol, SDK version, and deployment configuration during implementation and before production release.
