<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="/assets/css/style.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

    <title>Your account is blocked</title>
</head>
<body>
    <a href="/" class="logo" style="justify-content:center; font-size: 38px;">
        <span>DONUT<span class="green-x">TRIX</span></span>
    </a>
    <div class="main__error">
        <div class="code__error" style="margin-bottom: 10px;">Your account is blocked</div>
        <div class="error__text"><span>{{Auth::user()->username}}, you have been blocked. Please contact support for details.</div>
        <div class="error__text">Support Discord: <a href="https://discord.gg/qKMrjbMWVH" target="_blank" rel="noopener" class="colored-link">discord.gg/qKMrjbMWVH</a></div>
</body>
</html>