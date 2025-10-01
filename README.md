# REDCap User Profile  - ARCHIVED

**This repository has been archived and is no longer actively maintained.**

This project was archived as of October 1, 2025. The project for which it was written is now over. There's no funding to provide further maintenance for other projects. Please don't hesitate to use this code in accordance with the license; however, the authors are unable to offer any additional support.



---------------------

REDCap User Profile is an external module that extends user accounts information according to your needs - e.g. address, country of birth, job position, etc. This module provides:

- An easy way to manage user profiles
- An API to assist developers in accessing user profiles information

## How does it work?
This module uses a REDCap project to manage and store additional user attributes as data entry records.

## Prerequisites
- REDCap >= 8.0.3

## Installation
- Clone this repo into to `<redcap-root>/modules/redcap_user_profile_v<version_number>`.
- Go to **Control Center > External Modules** and enable User Profile - it will be enabled for all projects by default.

## Configuration

### Making sure authentication is enabled
If you are working in a test instance, you must turn on some form of authentication as this module builds upon account management features. Table-based authentication will work fine.

### Create a User Profile project
Create a REDCap project in order to extend user information according to your needs - e.g. address, country of birth, job position, etc. **Make sure to create a field that represents REDCap username** - that's how user accounts and profiles are connected.  A sample user profile project is available in [samples folder](samples/UserProfile.xml)

### Filling the settings form
Go to **Control Center > External Modules**, click on User Profile's **Configure** button, and fill the form as follows:
  - **Project**: The project you created
  - **Username field**: The key of username field you created

## Managing User Profiles
You can manage user profiles in two ways.

### Option 1
Directly on User Profile project - creating new records, making sure to associate a REDCap username for each profile.

### Option 2
By accessing user account page (go to **Control Manager > Browse Users** and click on **View Users** to choose the account).

There, you will be able to see a **Create User Profile** button or an **Edit User Profile** button. Either button will redirect you to the user profile form - for new profiles, the username field will be automatically prefilled.

## API usage
User Profile module provides `UserProfile` class. Here is an example of usage:

```php
<?php

use UserProfile\UserProfile;

$profile = new UserProfile('test_username');
$data = $profile->getProfileData();

// Assuming your User Profile project contains an address field which key is 'street_address'.
$address = $data['street_address'];
```

Here are other methods that might be useful:

```php
<?php

// Gets profile record ID.
$profile->getProfileId();

// Gets username.
$profile->getUsername();

// Gets username field key.
$profile->getUsernameField();

// Gets user profile project ID.
$profile->getProjectId();
```

There is also a static method to get all available profiles.

```php
<?php

use UserProfile\UserProfile;

$profiles = UserProfiles::getProfiles();

// Creating an array of addresses, keyed by username.
$addresses = array();
foreach ($profiles as $username => $profile) {
    $data = $profile->getProfileData();
    $addresses[$username] = $data['street_address'];
}
```
