<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Payme</title>
</head>
<body>
<form method="POST" action="https://checkout.paycom.uz/" name="check">
    @lang('strings.pls_wait')
    <input type="hidden" name="merchant" value="{{ $params['merchant']  }}"/>
    <input type="hidden" name="amount" value="{{ $params['amount']  }}"/>
    <input type="hidden" name="account[order_id]" value="{{ $params['account[order_id]']  }}"/>
    <input type="hidden" name="lang" value="{{ $params['lang']  }}"/>
    <input type="hidden" name="currency" value="{{ $params['currency']  }}"/>
    <input type="hidden" name="callback" value="{{ $params['callback']  }}"/>
    <input type="hidden" name="callback_timeout" value="{{ $params['callback_timeout']  }}"/>
    <input type="submit" style="display: none;">
</form>
<script>
    window.onload = function(){
        document.forms['check'].submit();
    }
</script>

</body>
</html>
