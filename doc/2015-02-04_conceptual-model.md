# Conceptual model

## Activation states

Each user has two possible activation states:
- activated
    - these users are forced to use 2FA
- not activated
    - these users are unable to use 2FA

Their activation states depend upon the activation states of all sites of which they are a member, and their personal options (only modifiable by superadmins).

Site states, which can be set on a per-site basis by superadmins:
- enabled
    - all users of this site must use 2FA
- disabled (default)
    - users of this site are not forced to use 2FA unless they're also a member of another site which forces them

A superadmin may override the activation state of a user using the following override options:
- no override (default)
    - the user's state is dependent upon the most restrictive site of which they are a member
- enable 2FA
    - irregardless of the sites the user belongs to, the user is in the activated state
- disable 2FA
    - irregardless of the sites the user belongs to, the user is in the not activated state

## Setup state

Each user has two possible setup states:
- setup incomplete
    - the user has no devices configured for their account
- setup complete
    - the user has at least one device configured for their account
