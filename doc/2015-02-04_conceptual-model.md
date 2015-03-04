# Conceptual model

## Enabled/disabled states

Each user has two possible states:
- enabled
    - these users are forced to use 2FA
- not enabled
    - these users are unable to use 2FA

Their enabled state depends upon the states of all sites of which they are a member, and their personal options (only modifiable by superadmins).

Site states, which can be set on a per-site basis by superadmins:
- enabled
    - all users of this site must use 2FA
- disabled (default)
    - users of this site are not forced to use 2FA unless they're also a member of another site which forces them

A superadmin may override the state of a user using the following override options:
- no override (default)
    - the user's state is dependent upon the most restrictive site of which they are a member
- enable 2FA
    - irregardless of the sites the user belongs to, the user is in the activated state
- disable 2FA
    - irregardless of the sites the user belongs to, the user is in the not activated state

Note that the enabled status only states that a user must use 2FA. Users can have 2FA enabled but not have any devices configured yet.

If a user has 2FA enabled it does not affect existing sessions, but when logging into a new session if their activation is incomplete then they must configure a device to proceed.

## Activation states

Each user has two possible activation states:
- activation incomplete
    - the user has no devices configured for their account
- activation complete
    - the user has at least one device configured for their account
