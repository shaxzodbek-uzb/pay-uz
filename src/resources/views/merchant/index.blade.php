<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Please, wait...</title>
</head>
<body>
Please, wait...
<form id="click_form" action="{{ $params['url'] }}" method="post" name="check">
    @foreach($params as $key  => $param)
        @if($key != 'url')
        <input type="hidden" name="{{ $key }}" value="{{ $params[$key] }}" />
        @endif
    @endforeach
    <button type="submit" style="display:none;">pay</button>
    <script>
        window.onload = function(){
            document.forms['check'].submit();
        }
    </script>
</form>

</body>
</html>
