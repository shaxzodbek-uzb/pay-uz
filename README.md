# Для национальных платежных систем в Узбекистане

[![Latest Version on Packagist](https://img.shields.io/packagist/dt/goodoneuz/pay-uz.svg?style=flat)](https://packagist.org/packages/goodoneuz/pay-uz)
[![Build Status](https://img.shields.io/travis/goodoneuz/pay-uz/master.svg?style=flat-square)](https://travis-ci.org/goodoneuz/pay-uz)
[![Quality Score](https://img.shields.io/scrutinizer/g/goodoneuz/pay-uz.svg?style=flat-square)](https://scrutinizer-ci.com/g/goodoneuz/pay-uz)

**Featured**
------
- [Payme](http://payme.uz) - Merchant
- [Click](http://click.uz) - Merchant
- [Oson](http://click.uz) - Merchant
- [Uzcard](http://uzcard.uz) - Merchant
- [Paynet](http://paynet.uz) - Merchant
- [Stripe](https://stripe.com/) - Merchant(Subscribe)

**Planned**
------
- Upay
- Visa


## Installation

You can install the package via composer:

```bash
composer require goodoneuz/pay-uz
```
Publishing required files of package:

```bash
php artisan vendor:publish --provider="Goodoneuz\PayUz\PayUzServiceProvider"
```
Migrate tables:

```bash
php artisan migrate
```

Seed settings:

```bash
php artisan db:seed --class="Goodoneuz\PayUz\database\seeds\PayUzSeeder"
```

## Usage
------
Placing routes for service in web.php

```php

//here comes requests from payment system
Route::any('/handle/{paysys}',function($paysys){
    PayUz::driver($paysys)->handle();
});

//here user redirects to payment system
Route::any('/redirect/{paysys}/{user_id}/{amount}',function($paysys, $user_id, $amount){
    $user = App\User::find($user_id);
    $url = 'https://payment.uz';
    PayUz::driver($paysys)->redirect($user, $amount, 860, $url);
});
```

**Exception:**
------

PaymentException 

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email shaxzodbek.qambaraliyev@gmail.com instead of using the issue tracker.

## Credits

- [Shaxzodbek](https://github.com/shaxzodbek-uzb)
- [Azizbek](https://github.com/azizbekeshonaliyev)
- [Rustam Mamadaminov](https://github.com/rustamwin)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
