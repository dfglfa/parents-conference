import csv
import random

firstnames = ['Adelheid', 'Bernhard', 'Conrad', 'Dorothea', 'Erwin', 'Frieda', 'Gustav', 'Hedwig', 'Irma', 'Jakob', 'Karl', 'Lieselotte', 'Mathilde', 'Otto', 'Paula', 'Rüdiger', 'Siegfried', 'Theodor', 'Wilhelm', 'Helga']
lastnames = [
    'von Breytenbach', 'León', 'Durand', 'García', 'Rousseau', 'López', 'Dubreuil', 
    'Fernández', 'Martínez', 'Delacroix', 'Schwerin', 'de la Cruz', 'Montalbán', 
    'Hollstein', 'Álvarez', 'Beaumont', 'De la Fuente', 'Dubois', 'Leclerc', 'Escudero', 
    'Pereira', 'Villeneuve', 'Sáez', 'Müllhausen', 'Leblanc', 'Castellanos', 'Moreau', 
    'Serrano', 'Waldorf', 'Espinosa', 'Kaufmann', 'Bautista', 'Chávez', 'Blanchard', 
    'González', 'Peñalosa', 'Tremblay', 'Boucher', 'Zambrano', 'Lemoine', 'Ortega', 
    'Quintana', 'Esquivel', 'Pascal', 'Navarro', 'Dumont', 'Figueroa', 'Reyes', 'Riquelme', 
    'Salcedo', 'de Montigny'
]

rooms = [f"R{n}" for n in range(100, 300)]
classes = [f"{i}{c}" for i in range(5, 13) for c in ['a', 'b', 'c']]

def gen_email(firstname, lastname):
    return f"{firstname.lower()}.{lastname.lower()}@lehrer.de"

csv_file = 'teachers.csv'

with open(csv_file, mode='w', newline='', encoding='utf-8') as file:
    writer = csv.writer(file, delimiter=';')
    
    writer.writerow(["Vorname", "Nachname", "E-Mail", "Klasse", "Benutzername", "Passwort", "Titel", "Raumnummer", "Raumname"])
    names = set([])
    
    for _ in range(100):
        firstname = random.choice(firstnames)
        lastname = random.choice(lastnames)
        while (firstname, lastname) in names:
            firstname = random.choice(firstnames)
            lastname = random.choice(lastnames)
        names.add((firstname, lastname))
        
        email = gen_email(firstname, lastname)
        cls = random.choice(classes)
        room = random.choice(rooms)
        username = f"{firstname.lower()}.{lastname.lower()}"
        password = "password"
        title = random.choice(["", "", "", "", "", "", "Dr.", "Mag.", "StD"])
        writer.writerow([firstname, lastname, email, cls, username, password, title, room, "Sprechzimmer"])
