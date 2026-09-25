<!DOCTYPE html>
<html lang="it">
<head><meta charset="utf-8"><title>Nuovo messaggio dal sito Nexxworth</title></head>
<body style="font-family: Arial, sans-serif; color: #13233a; line-height: 1.6;">
    <h1>Nuovo messaggio dal sito Nexxworth</h1>
    <p><strong>Nome:</strong> {{ $contactData['name'] }}</p>
    <p><strong>Email:</strong> {{ $contactData['email'] }}</p>
    <p><strong>Telefono:</strong> {{ $contactData['phone'] ?? 'Non indicato' }}</p>
    <h2>Messaggio</h2>
    <p style="white-space: pre-wrap;">{{ $contactData['message'] }}</p>
</body>
</html>
