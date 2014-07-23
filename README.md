# Honeypot Form

A form which provides a honeypot field and methods to check it.
This field is an input field in the form that human users are instructed not to fill out.  In any case, the field is normally hidden from the users.
Spambots will typically fill out all fields, and so we can check if this field has been filled when it shouldn't have.

## Installation
Install this in your SilverStripe checkout wherever you like.
Or use Composer..

## Usage

Wherever you'd normally use a `Form`, you may now use a `HoneypotForm`. You can inherit from it or use it in-place.

In your controller's `init` method, you'll probably want to call the following
```php
        HoneypotForm::render_css();
```
which will add CSS to the page in order to hide the honeypot field from the user.

## Validation
To check if the 'fly' has fallen into the honeypot, ie if a spambot is using the form, use the following in your form processing function.
```php
    public function myFormSubmission($data, $form) {
        if ($form->validateHoneypot($data)) {
            // User is either a bot, or very bad at following instructions!
            // ...
        } else {
            // The form looks okay
            /// ...
        }
        ...
```

## Bot tricks
To try to make it harder for the bots, the field name and class changes all the time, so they can't simply avoid fields of class 'honeypot' or something like this.