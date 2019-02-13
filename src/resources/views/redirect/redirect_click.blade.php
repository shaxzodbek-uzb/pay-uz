<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>CLICK</title>
</head>
<body>
@lang('strings.pls_wait')...
<form id="click_form" action="https://my.click.uz/pay/" method="post" name="check">
    <input type="hidden" name="MERCHANT_TRANS_AMOUNT" value="{{ $params['MERCHANT_TRANS_AMOUNT'] }}" />
    <input type="hidden" name="MERCHANT_ID" value="{{ $params['MERCHANT_ID'] }}"/>
    <input type="hidden" name="MERCHANT_USER_ID" value="{{ $params['MERCHANT_USER_ID'] }}"/>
    <input type="hidden" name="MERCHANT_SERVICE_ID" value="{{ $params['MERCHANT_SERVICE_ID'] }}"/>
    <input type="hidden" name="MERCHANT_TRANS_ID" value="{{ $params['MERCHANT_TRANS_ID'] }}"/>
    <input type="hidden" name="MERCHANT_TRANS_NOTE" value="{{ $params['MERCHANT_TRANS_NOTE'] }}"/>
    <input type="hidden" name="MERCHANT_USER_PHONE" value="{{ $params['MERCHANT_USER_PHONE'] }}"/>
    <input type="hidden" name="MERCHANT_USER_EMAIL" value="{{ $params['MERCHANT_USER_EMAIL'] }}"/>
    <input type="hidden" name="SIGN_TIME" value="{{ $params['SIGN_TIME'] }}"/>
    <input type="hidden" name="SIGN_STRING" value="{{ $params['SIGN_STRING'] }}"/>
    <input type="hidden" name="RETURN_URL" value="{{ $params['RETURN_URL'] }}"/>
    <button type="submit" style="display:none;">@lang('strings.pay_click')</button>
    <script>
        window.onload = function(){
            document.forms['check'].submit();
        }
    </script>
</form>

</body>
</html>
