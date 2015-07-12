blockchainwalletmock
====================

Mocked version of the blockchain.info wallet API for automated testing.

About
-----

This is a mocked version of the API at https://blockchain.info/api/blockchain_wallet_api that uses a local database to store transactions. No bitcoins will be moved to or from any wallet and no fees involving real coins will have to be paied. Since we are dealing with financial software, it doesn't hurt if we get into the habit of writing tests for our code. In order to do that, e we can use this tool to see if we deal with responses and handle callbacks in the correct way.

Getting started
---------------

First, install with:

``` bash
composer global require blockchainwalletmock
```

And the run with:

``` bash
blockchainwalletmock --port 8888
```

Once it is started we can see that it is up with:

``` bash
curl "http://localhost:8888/list?password=testpassword"
```

Which should gice us the result:
``` javascript
{
  "addresses":[
  ]
}
```

The JSON comes out as raw text, it has been formatted here for readability. We can create a new address in the same way as we would using the blockchin.info api:

``` bash
curl "http://localhost:8888/new_address?password=testpassword"
```
``` javascript
{
  "address": "075bd7684e782dca00007f01b24c34c0"
}
```

Now let's simulate an incoming payment:
``` bash
curl "http://localhost:8888/debug_incoming?password=testpassword&address=075bd7684e782dca00007f01b24c34c0&amount=100000000"
```
``` javascript
{
  "message":"ok"
}
```

Let's list our addresses again:
```bash
curl "http://localhost:8888/list?password=testpassword"
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

This way we can test our code before deployment, as well as create unit test using e.g. [PHPUnit](http://phpunit.de/), without having to worry about what happens to our bitcoins.

Configuration
-------------

There are various configuration options we can set, for example what minimum transaction the should be accepted. We can also set a callback url in the same way as we do in the blockchain.info "Account Settings", and this url will be called using the same parameters. Run blockchainwalletmock without parameters to see a list of available options.

Starting from within a test
---------------------------

API
---

The goal is that the API should follow that of https://blockchain.info/api/blockchain_wallet_api, but it is not 100% complete currently. I have only implemented what I need for my own purpose so far. If there is something missing give me a shout and I might be interested in adding the functions you need, or feel free to contribute... :)

The ID:s for addresses and transaction hashes are not real bitcoin addresses, but actually random MD5 sums. This is deliberate in order to not confuse them with real bitcoin addresses.

Apart from the functions docummented there, there are some special ones prefixed with `debug_` that we can use for debugging.

`/debug_incoming?address=$address&amount=$amount`

* __address__ The address that should receive an incoming payment.
* __amount__ The amount to add to the balance of the address.

Simulates an incoming transactions. If we have a callback registered it will be called in the same way as when we use the blockchain.info API.

`debug_confirmation?address=$address&transaction=$transaction&confirmations=$confirmations`

* __address__ _Optional._ The address that should receive confirmations for all its transactions.
* __transaction__ _Optional._ The transaction hash of the transaction that should receive a confirmation.
* __confirmations__ _Optional._ The number of confirmations to add.

Simulates one or several confirmations. Both the address and transaction hash is optional, in which case all transactions will receive a confirmation.

`/debug_clear`

Clear all the data in the database.
