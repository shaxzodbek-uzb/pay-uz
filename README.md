
# Для национальных платежных систем в Узбекистане
[Видео документация <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/0/09/YouTube_full-color_icon_%282017%29.svg/1200px-YouTube_full-color_icon_%282017%29.svg.png" width="26">](https://www.youtube.com/playlist?list=PLIU-yN_rFScVbbglNYmucY3TKzrxypEaP)

<a href="https://payme.uz/@shaxzodbek_" target="_blank"><img src="https://www.buymeacoffee.com/assets/img/custom_images/orange_img.png" alt="Buy Me A Coffee" style="height: 41px !important;width: 174px !important;box-shadow: 0px 3px 2px 0px rgba(190, 190, 190, 0.5) !important;-webkit-box-shadow: 0px 3px 2px 0px rgba(190, 190, 190, 0.5) !important;" ></a>

[![Latest Version on Packagist](https://img.shields.io/packagist/dt/goodoneuz/pay-uz.svg?style=flat)](https://packagist.org/packages/goodoneuz/pay-uz)
[![Build Status](https://img.shields.io/travis/shaxzodbek-uzb/pay-uz/master.svg?style=flat-square)](https://travis-ci.org/shaxzodbek-uzb/pay-uz)
[![Quality Score](https://img.shields.io/scrutinizer/g/shaxzodbek-uzb/pay-uz.svg?style=flat-square)](https://scrutinizer-ci.com/g/shaxzodbek-uzb/pay-uz)

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

//handle requests from payment system
Route::any('/handle/{paysys}',function($paysys){
    (new Goodoneuz\PayUz\PayUz)->driver($paysys)->handle();
});

//redirect to payment system or payment form
Route::any('/pay/{paysys}/{key}/{amount}',function($paysys, $key, $amount){
	$model = Goodoneuz\PayUz\Services\PaymentService::convertKeyToModel($key);
    $url = request('redirect_url','/'); // redirect url after payment completed
    $pay_uz = new Goodoneuz\PayUz\PayUz;
    $pay_uz
    	->driver($paysys)
    	->redirect($model, $amount, 860, $url);
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
