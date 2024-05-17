<?php

namespace Mongo\Auth;

use Mongo\Database\Collection;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Notifications\Notifiable;

class MongoAuthenticable extends Collection implements AuthenticatableContract, CanResetPassword
{
    use Notifiable;

    /**
     * Authenticable
     */
    public function getAuthIdentifierName()
    {
        return $this->getKeyName();
    }

    public function getAuthIdentifier()
    {
        return $this->getKey();
    }

    public function getAuthPasswordName()
    {
        return 'password';
    }

    public function getAuthPassword()
    {
        return $this->get($this->getAuthPasswordName());
    }

    public function getRememberTokenName()
    {
        return 'remember_token';
    }

    public function getRememberToken()
    {
        return $this->get($this->getRememberTokenName());
    }

    public function setRememberToken($value)
    {
        $this[$this->getRememberTokenName()] = $value;
    }

    /**
     * Can reset password
     */
    public function getEmailForPasswordReset()
    {
        return $this->email;
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function save()
    {
        // placeholder
    }
}
