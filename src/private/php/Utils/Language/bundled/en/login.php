<?php
/*
 * TeamSpeak login modal (templates/utils/modal-login.latte, js/login.js).
 * Translations stored in the database always win, see core.php.
 */
return [
    "LOGIN_TITLE" => "Log in with TeamSpeak",
    "LOGIN_LOADING" => "Loading",

    "LOGIN_PROGRESS" => "Login steps",
    "LOGIN_STEP_CONNECT" => "Connect",
    "LOGIN_STEP_ACCOUNT" => "Choose account",
    "LOGIN_STEP_CODE" => "Enter code",
    "LOGIN_STEP_DONE" => "done",

    "LOGIN_NOT_CONNECTED" => "You're not on the server yet",
    "LOGIN_NOT_CONNECTED_TEXT" => "Connect to the server from this device. This window moves on by itself as soon as the server sees you.",
    "LOGIN_WAITING" => "Waiting for you to connect",
    "LOGIN_DEBUG_IP" => "IP address the website sees",

    "LOGIN_SELECT_ACCOUNT" => "Choose your account",
    "LOGIN_SELECT_ACCOUNT_TEXT" => "Several TeamSpeak clients are connected from your address.",
    "LOGIN_ACCOUNT_ID" => "ID {0}",

    "LOGIN_ENTER_CODE" => "Enter the code",
    "LOGIN_CODE_SENT_POKE" => "We sent you a confirmation code in TeamSpeak as a poke.",
    "LOGIN_CODE_SENT_MESSAGE" => "We sent you a confirmation code in a TeamSpeak private message.",
    "LOGIN_CODE_LABEL" => "Confirmation code",
    "LOGIN_CODE_VALID" => "Codes are valid for {0}",
    "LOGIN_DURATION_MIN" => "{0}\u{00A0}min",
    "LOGIN_DURATION_SEC" => "{0}\u{00A0}s",
    "LOGIN_CODE_INVALID" => "Wrong or expired code",
    "LOGIN_CODE_RESEND" => "Resend code",
    "LOGIN_CODE_RESENT" => "We sent you a new code",
    "LOGIN_CODE_STILL_VALID" => "Your last code is still valid",
    "LOGIN_SUBMIT" => "Log in",
    "LOGIN_OTHER_ACCOUNT" => "Other account",

    "LOGIN_ERROR_LOAD" => "Couldn't load the login data. The TeamSpeak server may be offline.",
    "LOGIN_ERROR_SEND" => "Couldn't send you the code. Check that you are still on the server and that the bot is allowed to message you.",
    "LOGIN_RETRY" => "Try again",
];
