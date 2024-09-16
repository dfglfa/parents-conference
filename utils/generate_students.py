import csv
import random

firstnames = ['Anna', 'Ben', 'Clara', 'David', 'Eva', 'Felix', 'Greta', 'Hannah', 'Jan', 'Lena', 'Max', 'Nina', 'Oliver', 'Paul', 'Quentin', 'Rita', 'Sophie', 'Tom', 'Uwe', 'Vera', 'Walter', 'Xenia', 'Yannick', 'Zoe']
lastnames = [
    'Águila', 'Brunet', 'Casanova', 'Delgado', 'Echeverría', 'Fontaine', 'Gonzaga', 
    'Huguet', 'Iglesias', 'Jourdain', 'Kraus', 'Lachapelle', 'Mendoza', 'Nevárez', 
    'Orozco', 'Pacheco', 'Quirós', 'Ríos', 'Saavedra', 'Téllez', 'Urrutia', 'Vargas', 
    'Weinstein', 'Ybarra', 'Zapata', 'Abascal', 'Barragán', 'Clément', 'Deschamps', 
    'Elizondo', 'Favre', 'Gascón', 'Hidalgo', 'Ibarra', 'Joubert', 'Kastner', 'Leroux', 
    'Montoya', 'Nadal', 'Olivares', 'Pujol', 'Ramírez', 'Santos', 'Thibault', 'Urquiza', 
    'Vidal', 'Wiesner', 'Ximénez', 'Yturralde', 'Zaragoza', 'Dupré'
]

classes = [f"{i}{c}" for i in range(5, 13) for c in ['a', 'b', 'c']]

def gen_email(firstname, lastname):
    return f"{firstname.lower()}.{lastname.lower()}@school.net"

csv_file = 'students.csv'

with open(csv_file, mode='w', newline='', encoding='utf-8') as file:
    writer = csv.writer(file, delimiter=';')
    
    writer.writerow(["Vorname", "Nachname", "E-Mail", "Klasse", "Benutzername", "Passwort", "Geschwister"])
    names = set([])
    
    for _ in range(200):
        firstname = random.choice(firstnames)
        lastname = random.choice(lastnames)
        while (firstname, lastname) in names:
            firstname = random.choice(firstnames)
            lastname = random.choice(lastnames)
        names.add((firstname, lastname))
        
        email = gen_email(firstname, lastname)
        cls = random.choice(classes)
        username = f"{firstname.lower()}.{lastname.lower()}"
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
