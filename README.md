# Parents conference

This project can be used to organize a parent-teacher conference at your school.

Students can access the system to view their appointments, as well as those of their brothers and sisters.

Teachers on the other hand can view the booked slots and manage their attendance time (as long as the booking phase has not yet begun).
Optionally they can cancel a meeting with a student and provide a reason. The student will then be informed by mail as well as in the app.

## Installation

You need a server with php8 and MySQL installed.

PHP needs the extensions pdo_mysql, zip and ldap.

You can create the needed database with the SQL script provided in the `Setup` folder.
Furthermore you have to enter your database credentials in the settings.ini file contained in code/dao.

Rename the config template to serve as the actual config:

```
mv code/config.php_TEMPLATE code/config.php
```

Edit this file to match your email and LDAP server settings.
If you do not intend to use LDAP, leave it unchanged, since by default, LDAP is not enabled.

## Usage

As an administrator:

0. Upload the logo of your school
1. Import teachers via a CSV file (example in templates/teachers.csv)
2. Import student data via a CSV file (example in templates/students.csv)
3. Optionally: Configure email templates with text according to your wishes.
4. Create a reunion.
5. Optionally: Print passwords and distribute them to the students.

As a teacher (optional):

1. Set the time range you are present (only possible until booking begins).
2. After booking has begun: View and possibly cancel appointments.

As a student / parent:

1. Log in with the provided credentials.
2. Book the desired slots for the desired teacher. If your siblings are connected to your account, you have a unified booking "matrix" for all siblings.
3. Print your time-table.

## Online Reunion

Online conferences are supported. When creating a conference you can specify a base URL for a video conferencing service. (E.g. https://meet.jit.si)
Teachers and students will see a unique link for each booked slot.

## Development

Refer to the Readme in the Docker subdirectory.
