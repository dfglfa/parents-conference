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
