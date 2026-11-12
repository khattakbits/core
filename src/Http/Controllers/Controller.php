<?php

namespace KhattakBits\Core\Http\Controllers;

/**
 * Base controller for core package controllers.
 *
 * Host ka `App\Http\Controllers\Controller` jaan boojh kar use nahi hota: package
 * ko host par depend karna mana hai. Host apne controllers ke liye apna base
 * rakhta hai — dono classes independent hain, koi inheritance nahi.
 */
abstract class Controller
{
    //
}
