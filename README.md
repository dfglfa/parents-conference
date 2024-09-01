# Reunion

This project can be used to easily and effectively hold a parent-teacher-reunion in your school.
The administrator can import data for teachers and students and create a reunion.
The newsletter filled with the needed access data so that parents can log in and book their desired time slots for the teachers they want to meet can then be created automatically.

## Installation

You can create the needed database with the SQL script provided in the setup folder.
Furthermore you have to enter your database credentials in the settings.ini file contained in code/dao.
If you want to quickly try out the tool there is also a SQL script provided in the setup folder which puts some dummy test data in your newly created database.

Be aware that the UI is in german.

## Usage

As an administrator:

0. Upload the logo of your school to img/logo.png on your server (by default, this is the logo of the DFG Freiburg)
1. Import teachers via a CSV file.
2. Import student data via a CSV file.
3. Upload a newsletter template in ODT format.
4. Create a reunion.
5. Create the newsletter and distribute it among the students / parents.

As a teacher (optional):

1. Set the time range you are present.

As a student / parent:

1. Log in with the credentials provided on the newsletter.
2. Book the desired slots for the desired teacher.
3. Print your time-table.

## Online Reunion

We added support to hold the reunion online. When you create a new reunion you can now specify a base URL for a video conferencing service. (E.g. https://meet.jit.si)
Teachers and students will find an individal link for each booked slot, where they can meet online.

## Development

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
