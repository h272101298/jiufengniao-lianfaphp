---
title: API Reference

language_tabs:
- bash
- javascript

includes:

search: true

toc_footers:
- <a href='http://github.com/mpociot/documentarian'>Documentation Powered by Documentarian</a>
---
<!-- START_INFO -->
# Info

Welcome to the generated API reference.
[Get Postman Collection](http://localhost/docs/collection.json)
<!-- END_INFO -->

#general
<!-- START_8c0e48cd8efa861b308fc45872ff0837 -->
## 微信用户登录

> Example request:

```bash
curl -X POST "http://localhost/Shop/public/api/v1/login" \
-H "Accept: application/json"
```

```javascript
var settings = {
    "async": true,
    "crossDomain": true,
    "url": "http://localhost/Shop/public/api/v1/login",
    "method": "POST",
    "headers": {
        "accept": "application/json"
    }
}

$.ajax(settings).done(function (response) {
    console.log(response);
});
```


### HTTP Request
`POST api/v1/login`


<!-- END_8c0e48cd8efa861b308fc45872ff0837 -->

<!-- START_7596fe649d40d31907da5551e64047fd -->
## 后台用户登录

> Example request:

```bash
curl -X POST "http://localhost/Shop/public/v1/login" \
-H "Accept: application/json" \
    -d "username"="nulla" \
    -d "password"="nulla" \

```

```javascript
var settings = {
    "async": true,
    "crossDomain": true,
    "url": "http://localhost/Shop/public/v1/login",
    "method": "POST",
    "data": {
        "username": "nulla",
        "password": "nulla"
},
    "headers": {
        "accept": "application/json"
    }
}

$.ajax(settings).done(function (response) {
    console.log(response);
});
```


### HTTP Request
`POST v1/login`

#### Parameters

Parameter | Type | Status | Description
--------- | ------- | ------- | ------- | -----------
    username | string |  required  | 
    password | string |  required  | Minimum: `6`

<!-- END_7596fe649d40d31907da5551e64047fd -->

