You need to have Docker and Docker Compose installed. Then, run the commands

```
# Copy app config from template
cp code/config.php_TEMPLATE code/config.php

# Create (first time only) and run docker containers
cd Docker
docker-compose up -d
```

You can then visit your reunion instance at http://localhost

The code is mounted into the php-fpm docker container, so all code changes are reflected immediately.

To begin, login as admin/admin and start by importing student and teacher data in the "Administration" section. Use the files templates/students.csv and templates/teachers.csv for some initial data.
All users have the password "password". There are three students by the name of "Müller" with which you can explore the account connection feature for siblings.

Create an Elternsprechtag in the Administration panel.

Login as students/teachers to explore the booking process.

All email will be sent to a "mailcatcher" instance that is running at http://localhost:1080

# Activating LDAP in the docker setup

First, set the config variable `$LDAP_ENABLED` to `true` in the config.php.

Generate a users LDIF file with the script in `utils/generate_users.py`, which will generate three files:

- students.csv
- teachers.csv
- users.ldif

The latter can then be added the ldap docker container via

```
ldapadd -h localhost:1389 -x -D "cn=admin,dc=example,dc=org" -W -f users.ldif
```

using the admin password `adminpassword`.
