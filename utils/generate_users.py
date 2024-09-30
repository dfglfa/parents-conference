import csv
import random
import hashlib
import base64
import os
import unicodedata

def generate_ssha(password):
    salt = os.urandom(8)
    sha1_hash = hashlib.sha1(password.encode('utf-8'))
    sha1_hash.update(salt)
    digest = sha1_hash.digest() + salt
    return "{SSHA}" + base64.b64encode(digest).decode('utf-8')


def remove_accents(input_string):
    normalized_string = unicodedata.normalize('NFD', input_string)
    ascii_string = ''.join([char for char in normalized_string if unicodedata.category(char) != 'Mn'])
    return ascii_string

def normalize(name):
    return remove_accents(name).replace(" ", "")

rooms = [f"R{n}" for n in range(100, 300)]
classes = [f"{i}{c}" for i in range(5, 13) for c in ['a', 'b', 'c']]

# LDAP users file
ldif_file = 'users.ldif'
with open(ldif_file, "w") as ldif:
    ldif.write("""dn: ou=users,dc=example,dc=org
objectClass: organizationalUnit
ou: users
description: Organizational unit for storing user accounts\n\n""")


def gen_email(username, domain):
    return f"{username}@{domain}"

student_firstnames = ['Anna', 'Ben', 'Clara', 'David', 'Eva', 'Felix', 'Greta', 'Hannah', 'Jan', 'Lena', 'Max', 'Nina', 'Oliver', 'Paul', 'Quentin', 'Rita', 'Sophie', 'Tom', 'Uwe', 'Vera', 'Walter', 'Xenia', 'Yannick', 'Zoe']
student_lastnames = [
    'Águila', 'Brunet', 'Casanova', 'Delgado', 'Echeverría', 'Fontaine', 'Gonzaga', 
    'Huguet', 'Iglesias', 'Jourdain', 'Kraus', 'Lachapelle', 'Mendoza', 'Nevárez', 
    'Orozco', 'Pacheco', 'Quirós', 'Ríos', 'Saavedra', 'Téllez', 'Urrutia', 'Vargas', 
    'Weinstein', 'Ybarra', 'Zapata', 'Abascal', 'Barragán', 'Clément', 'Deschamps', 
    'Elizondo', 'Favre', 'Gascón', 'Hidalgo', 'Ibarra', 'Joubert', 'Kastner', 'Leroux', 
    'Montoya', 'Nadal', 'Olivares', 'Pujol', 'Ramírez', 'Santos', 'Thibault', 'Urquiza', 
    'Vidal', 'Wiesner', 'Ximénez', 'Yturralde', 'Zaragoza', 'Dupré'
]

with open('students.csv', 'w', newline='', encoding='utf-8') as file, open(ldif_file, "a") as ldif:
    writer = csv.writer(file, delimiter=';')
    
    writer.writerow(["Vorname", "Nachname", "E-Mail", "Klasse", "Benutzername", "Passwort", "Geschwister"])
    names = set([])
    
    for _ in range(200):
        firstname = random.choice(student_firstnames)
        lastname = random.choice(student_lastnames)
        while (firstname, lastname) in names:
            firstname = random.choice(student_firstnames)
            lastname = random.choice(student_lastnames)
        names.add((firstname, lastname))
        
        cls = random.choice(classes)
        username = normalize(f"{firstname.lower()}.{lastname.lower()}")
        email = gen_email(username, "student.net")
        password = "password"
        sibling = ""
        
        # With a probability of 30%, a student shall have a sibling among the other students.
        # Search for an identical lastname with a different firstname among the names created so far.
        if random.random() > 0.7:
            for fname, lname in names:
                if lname == lastname and fname != firstname:
                    sibling = f"{lname}, {fname}"
                    break
        
        writer.writerow([firstname, lastname, email, cls, username, password, sibling])
        
        ldif.write(f"""dn: uid={username},ou=users,dc=example,dc=org
objectClass: inetOrgPerson
objectClass: top
cn: {firstname} {lastname}
sn: {lastname}
uid: {username}
userPassword: {generate_ssha("password")}
mail: {email}\n\n""")


teacher_firstnames = ['Adelheid', 'Bernhard', 'Conrad', 'Dorothea', 'Erwin', 'Frieda', 'Gustav', 'Hedwig', 'Irma', 'Jakob', 'Karl', 'Lieselotte', 'Mathilde', 'Otto', 'Paula', 'Rüdiger', 'Siegfried', 'Theodor', 'Wilhelm', 'Helga']
teacher_lastnames = [
    'von Breytenbach', 'León', 'Durand', 'García', 'Rousseau', 'López', 'Dubreuil', 
    'Fernández', 'Martínez', 'Delacroix', 'Schwerin', 'de la Cruz', 'Montalbán', 
    'Hollstein', 'Álvarez', 'Beaumont', 'De la Fuente', 'Dubois', 'Leclerc', 'Escudero', 
    'Pereira', 'Villeneuve', 'Sáez', 'Müllhausen', 'Leblanc', 'Castellanos', 'Moreau', 
    'Serrano', 'Waldorf', 'Espinosa', 'Kaufmann', 'Bautista', 'Chávez', 'Blanchard', 
    'González', 'Peñalosa', 'Tremblay', 'Boucher', 'Zambrano', 'Lemoine', 'Ortega', 
    'Quintana', 'Esquivel', 'Pascal', 'Navarro', 'Dumont', 'Figueroa', 'Reyes', 'Riquelme', 
    'Salcedo', 'de Montigny'
]

with open('teachers.csv', mode='w', newline='', encoding='utf-8') as file, open(ldif_file, "a") as ldif:
    writer = csv.writer(file, delimiter=';')
    
    writer.writerow(["Vorname", "Nachname", "E-Mail", "Klasse", "Benutzername", "Passwort", "Titel", "Raumnummer", "Raumname"])
    names = set([])
    
    for _ in range(100):
        firstname = random.choice(teacher_firstnames)
        lastname = random.choice(teacher_lastnames)
        while (firstname, lastname) in names:
            firstname = random.choice(teacher_firstnames)
            lastname = random.choice(teacher_lastnames)
        names.add((firstname, lastname))
        
        cls = random.choice(classes)
        room = random.choice(rooms)
        username = normalize(f"{firstname.lower()}.{lastname.lower()}")
        email = gen_email(username, "teacher.net")
        password = "password"
        title = random.choice(["", "", "", "", "", "", "Dr.", "Mag.", "StD"])
        writer.writerow([firstname, lastname, email, cls, username, password, title, room, "Sprechzimmer"])
        
        ldif.write(f"""dn: uid={username},ou=users,dc=example,dc=org
objectClass: inetOrgPerson
objectClass: top
cn: {firstname} {lastname}
sn: {lastname}
uid: {username}
userPassword: {generate_ssha("password")}
mail: {email}\n\n""")