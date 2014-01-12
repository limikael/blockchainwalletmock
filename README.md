blockchainwalletmock
====================

Mocked version of the blockchain.info wallet API for automated testing.

About
-----

This is a mocked version of the API at https://blockchain.info/api/blockchain_wallet_api that uses a local database to store transactions. Since we are dealing with financial software, it doesn't hurt if we get into the habit of writing tests for our code. In this case we can use this tool to see if we deal with responses and handle callbacks in the correct way.

Getting started
---------------

There is an example setup in the `example` folder. You can run it with

``` bash
cd blockchainwalletmock
php -S localhost:8888 -t example example/index.php
```

Once it is started we can see that it is up with:

``` bash
curl "http://localhost:8888/testwallet/list?password=testpassword"
```

Which should gice us the result: (the JSON comes out as raw text, it has been formatted here for readability)
``` javascript
{
  "addresses":[
  ]
}
```

We can create a new address in the same way as we would using the blockchin.info api:

``` bash
curl "http://localhost:8888/testwallet/new_address?password=testpassword"
```
``` javascript
{
  "address": "075bd7684e782dca00007f01b24c34c0"
}
```

Now lets simulate an incoming payment:
``` bash
curl "http://localhost:8888/testwallet/debug_incoming?password=testpassword&address=075bd7684e782dca00007f01b24c34c0&amount=100000000"
```
``` javascript
{
  "message":"ok"
}
```

Lets list our addresses again:
```bash
curl "http://localhost:8888/testwallet/list?password=testpassword"
```
```javascript
{
  "addresses":[
    {
      "address":"075bd7684e782dca00007f01b24c34c0",
      "balance":"100000000",
      "total_received":"100000000"
    }
  ]
}
```

You get the idea.

This way we can test our code before deployment, as well as create unit test using e.g. phpunit, without having to worry about what happens to our bitcoins.

Configuration
-------------

There are various configuration options we can set, for example what minimum transaction the should be accepted. We can also set a callback url in the same way as we do in the blockchain.info Account Settings, and this url will be called using the same parameters. Look at the index.php file in the example folder to see how this is done.

API
---

The goal is that the API should follow that of https://blockchain.info/api/blockchain_wallet_api, but it is not 100% complete currently. If there is something missing give me a shout and I might be interested in adding the functions you need, or feel free to contribute... :)

