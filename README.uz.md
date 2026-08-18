# O'zbekiston to'lov tizimlari uchun Laravel paketi

<a href="https://tirikchilik.uz/shaxzodbek-uzb" target="_blank"><img src="https://www.buymeacoffee.com/assets/img/custom_images/orange_img.png" alt="Buy Me A Coffee" height="41" width="174"></a>

[![Packagist'dagi oxirgi versiya](https://img.shields.io/packagist/v/goodoneuz/pay-uz.svg?style=flat-square)](https://packagist.org/packages/goodoneuz/pay-uz)
[![Yuklab olishlar](https://img.shields.io/packagist/dt/goodoneuz/pay-uz.svg?style=flat-square)](https://packagist.org/packages/goodoneuz/pay-uz)
[![Litsenziya](https://img.shields.io/packagist/l/goodoneuz/pay-uz.svg?style=flat-square)](LICENSE.md)

Merchant integratsiyalari, karta ekvayringi, takroriy to'lovlar, fiskallashtirish,
BNPL va elektron hisob-fakturalar — hammasi bitta paketda.

> **To'liq hujjat — [README.md](README.md).** Bu sahifa qisqacha ko'rinishi;
> quyidagi har bir qatlam o'sha yerdagi o'z bo'limiga havola qiladi.
> Also available [in English](README.en.md).

## Paket ichida nima bor

Har bir qatlam — almashtiriladigan drayverlar ustidagi fasad. Hammasida `null`
drayver bor, shuning uchun yangi o'rnatilgan paket internetsiz ham ishlaydi.

| Qatlam | Fasad | Provayderlar |
|---|---|---|
| Merchant callback'lari (redirect + webhook) | `PayUz` | [Payme](https://payme.uz), [Click](https://click.uz), [Paynet](https://paynet.uz), [Uzum Bank](https://uzumbank.uz) |
| Karta ekvayringi (hosted checkout, saqlangan karta, capture/refund) | `Checkout` | [Octo](https://octo.uz), [Multicard / Rahmat Pay](https://multicard.uz) |
| Takroriy to'lovlar / karta tokenizatsiyasi | `Subscribe` | [Payme Subscribe](https://developer.help.paycom.uz), [ATMOS](https://atmos.uz), [Stripe](https://stripe.com) |
| OFD fiskallashtirish (virtual kassa) | `Fiscalizer` | almashtiriladigan — IKPU/MXIK, QQS, fiskal belgi/QR |
| BNPL / bo'lib to'lash | `Bnpl` | [Uzum Nasiya](https://uzum.uz/nasiya) |
| Elektron hisob-faktura / ЭСФ | `Einvoice` | [Didox](https://didox.uz) |

**Kodingizda summalar doimo tiyinda.** Har bir drayver o'z chegarasida shlyuz talab
qilgan ko'rinishga o'giradi — masalan, Octo o'nlik somda hisob-kitob qiladi —
shuning uchun buni chaqiruv joyida qilish kerak emas.

Rejada: Upay, Visa. `PaymentSystem::OSON` va `::UZCARD` faqat tranzaksiya yorlig'i
sifatida mavjud, ular ortida ishlaydigan shlyuz yo'q.

## O'rnatish

```bash
composer require goodoneuz/pay-uz
```

Paket fayllarini nashr qilish:

```bash
php artisan vendor:publish --provider="Goodoneuz\PayUz\PayUzServiceProvider"
```

Migratsiya, so'ngra seed:

```bash
php artisan migrate
```

```bash
php artisan db:seed --class="Goodoneuz\PayUz\Database\Seeds\PayUzSeeder"
```

Shundan keyin Payme, Click, Uzum va Octo kalitlari boshqaruv panelidagi
`/payment/payment_systems` sahifasida kiritiladi. Nashr qilingan
`config/payuz.php` xuddi shu kalitlarni env orqali zaxira variant sifatida saqlaydi,
paneldagi bo'sh maydon esa sozlangan qiymatni hech qachon o'chirib yubormaydi.

## Foydalanish

Merchant oqimi — biri to'lov tizimining callback'larini qabul qiladi, ikkinchisi
mijozni to'lovga yuboradi:

```php
// to'lov tizimidan kelgan so'rovlarni qabul qilish
Route::any('/handle/{paysys}', function ($paysys) {
    (new Goodoneuz\PayUz\PayUz)->driver($paysys)->handle();
});

// to'lov tizimiga yoki uning to'lov formasiga yo'naltirish
Route::any('/pay/{paysys}/{key}/{amount}', function ($paysys, $key, $amount) {
    $model = Goodoneuz\PayUz\Services\PaymentService::convertKeyToModel($key);
    $url   = request('redirect_url', '/'); // to'lovdan keyin qaytadigan manzil

    (new Goodoneuz\PayUz\PayUz)
        ->driver($paysys)
        ->redirect($model, $amount, 860, $url);
});
```

Karta ekvayringi — hosted checkout, summa tiyinda:

```php
use Checkout;
use Goodoneuz\PayUz\Checkout\Payment;

$result = Checkout::pay(
    Payment::make(1_200_000, $order->id)        // tiyin
        ->describedAs('Buyurtma #'.$order->id)
        ->returnTo(route('checkout.return'))
        ->notifyAt(route('checkout.webhook'))
);

return redirect($result->payUrl());             // mijozni shlyuzga yuborish
```

Saqlangan kartalar, ikki bosqichli capture, refund, webhook'lar va Octo karta
bog'lash — [README.md ning Checkout bo'limida](README.md#card-acquiring-aggregator-octo).

To'lovga munosabat bildirish `PaymentResolver` va `Payments\Events\*` hodisalari
orqali amalga oshiriladi — [Payment hooks](README.md#payment-hooks-resolver--events)
bo'limiga qarang. Agar 3.0.0 dan oldingi versiyadan yangilanayotgan bo'lsangiz,
[README.md](README.md) oxiridagi ogohlantirishni o'qing: kod "muharriri" olib
tashlangan.

Xatoliklar `PaymentException` ni ko'taradi; Checkout, Subscribe, Bnpl va Einvoice
qatlamlarining o'z exception turlari bor.

## Testlash

```bash
composer test
```

## O'zgarishlar tarixi

Yaqinda nima o'zgarganini [CHANGELOG](CHANGELOG.md) dan ko'ring.

## Hissa qo'shish

Tafsilotlar uchun [CONTRIBUTING](CONTRIBUTING.md) ga qarang.

## Xavfsizlik

Xavfsizlikka oid muammo topsangiz, issue tracker o'rniga
shaxzodbek.qambaraliyev@gmail.com ga xat yozing.

## Mualliflar

- [Shaxzodbek](https://github.com/shaxzodbek-uzb)
- [Azizbek](https://github.com/azizbekeshonaliyev)
- [Rustam Mamadaminov](https://github.com/rustamwin)
- [Barcha hissa qo'shganlar](../../contributors)

## Litsenziya

MIT litsenziyasi (MIT). Batafsil ma'lumot uchun [litsenziya faylini](LICENSE.md) ko'ring.
