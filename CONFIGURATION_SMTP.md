# Configuration SMTP pour les emails

## Problème identifié
❌ **Erreur SMTP code 10060** : Connexion au serveur SMTP impossible
- Les identifiants actuels sont **incorrects** ou **non configurés**
- Adresse: `julien.e@me.com` 
- Mot de passe: `2145` (ne semble pas être un vrai mot de passe d'application)

## Solution : Configurer Gmail SMTP

### Étape 1 : Activer les applications moins sûres OU utiliser un mot de passe d'application

#### Option A : Mot de passe d'application (RECOMMANDÉ)
1. Aller à : https://myaccount.google.com/apppasswords
2. Sélectionner "Mail" et "Windows"
3. Copier le mot de passe généré (16 caractères)
4. Utiliser ce mot de passe dans la configuration

#### Option B : Autoriser les connexions moins sûres (moins recommandé)
1. Aller à : https://myaccount.google.com/lesssecureapps
2. Activer "Autoriser les applications moins sûres"

### Étape 2 : Modifier le fichier `contact_traitement.php`

Remplacer cette section (lignes ~88-102) :

```php
$smtpUser = "contact@golfdecherisey.fr";  // ← VOTRE adresse email
$smtpPass = "your_app_password";          // ← VOTRE mot de passe d'application (16 caractères)
$recipient = "contact@golfdecherisey.fr"; // ← Adresse qui reçoit les messages
```

**Exemple :**
```php
$smtpUser = "votre.email@gmail.com";      // Adresse Gmail pour l'authentification
$smtpPass = "abcd efgh ijkl mnop";        // Mot de passe d'application (avec espace)
$recipient = "admin@golfdecherisey.fr";   // Adresse qui reçoit les messages de contact
```

### Étape 3 : Tester les identifiants

1. Remplir et envoyer le formulaire de contact
2. Vérifier les logs : `c:\wamp64\logs\php_error.log`
3. Si erreur SMTP persiste, chercher la vraie raison dans les logs

## Paramètres SMTP Gmail (ne pas modifier)

```
Serveur SMTP  : smtp.gmail.com
Port          : 587
Sécurité      : TLS (starttls)
Authentification : Activée
```

## FAQ

### "Je n'ai pas de compte Gmail"
Vous pouvez :
1. Créer un compte Gmail gratuit : https://accounts.google.com/signup
2. Utiliser un autre serveur SMTP (Outlook, SendGrid, etc.)

### "Où trouver mon mot de passe d'application ?"
- https://myaccount.google.com/apppasswords (requi vous être connecté à Google)
- Vous devez d'abord avoir l'authentification 2FA activée

### "Les emails arrivent toujours en base mais pas par mail"
1. ✅ Les messages sont bien enregistrés en base (succès BDD)
2. ❌ L'envoi SMTP échoue (problème de configuration)
3. Consultez les logs pour le détail exact de l'erreur

## Vérification des logs

```powershell
# Terminal PowerShell
Get-Content -Path "c:\wamp64\logs\php_error.log" -Tail 50
```

Cherchez les lignes commençant par `Mail error` ou `SMTP Error`.
