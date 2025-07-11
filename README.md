# Padelles Beheersysteem

Een volledig webgebaseerd systeem voor het beheren en creëren van padel-lessen met een visuele editor.

## 🎯 Functies

### Gebruikersbeheer
- Login/register systeem met rollen: admin, trainer, viewer
- Sessiebeheer en rolgebaseerde toegangscontrole
- Trainers en admins kunnen lessen aanmaken/bewerken

### Lesbeheer
- Overzichtspagina met alle lessen per gebruiker
- Detailpagina met volledige lesinformatie
- Visuele editor met drag-and-drop functionaliteit
- Volledige persistentie van tekeningen

### Visuele Editor
- Canvas-gebaseerde editor met padelbaan achtergrond
- Toolbox met: Speler, Ballenmand, Pion, Flapje, Lijnen
- Drag-and-drop, rotatie, verwijdering van objecten
- Realtime opslag via AJAX
- Exacte herlading van bestaande lessen

## 🛠 Technologieën

- **Backend**: PHP 8.0+
- **Database**: MySQL 8.0+
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **UI Framework**: Bootstrap 5
- **Canvas**: HTML5 Canvas API
- **Security**: PDO, password_hash, CSRF protection

## 📁 Projectstructuur

```
/
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── config/
├── includes/
├── classes/
├── pages/
└── uploads/
```

## 🚀 Installatie

1. **Database setup**:
   ```sql
   CREATE DATABASE padelles_db;
   USE padelles_db;
   ```

2. **Configuratie**:
   - Kopieer `config/database.example.php` naar `config/database.php`
   - Vul database gegevens in

3. **Webserver**:
   - Plaats bestanden in webroot
   - Zorg dat PHP en MySQL geïnstalleerd zijn

4. **Eerste gebruiker**:
   - Ga naar `/register.php` om een admin account aan te maken

## 🔐 Beveiliging

- Wachtwoorden worden gehasht opgeslagen
- Prepared statements voor alle database queries
- CSRF- en XSS-bescherming
- Rolgebaseerde toegangscontrole

## 📊 Database Schema

### Users
- id, naam, email, wachtwoord_hash, rol

### Lessen
- id, titel, bedoeling, slag, niveaufactor, beschrijving, auteur_id, datum_aanmaak

### Les_items
- id, les_id, type, x, y, rotation, extra_data, z_index

## 🎨 Visuele Editor Features

- **Achtergrond**: Padelbaan afbeelding
- **Objecten**: Spelers, ballenmand, pionnen, flapjes, lijnen
- **Interactie**: Drag-and-drop, rotatie, verwijdering
- **Persistentie**: Realtime opslag en exacte herlading 