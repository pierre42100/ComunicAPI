# The Comunic API

This project is the main Comunic RestAPI. It assures data backend support.

(c) Pierre HUBERT since 2017

# Crons required for Comunic

## Calls cron
There is a cron to automatically cleanup old conversation. Ideally this cron should be executed every 30 seconds. The file to execute is `bin/clean_calls` 



# Use calls in Comunic
To use calls in Comunic, you need a WebRTCSignalExchangerServer, a small signal exchanging server written using NodeJS. You also need to modify your configuration file located at `config/overwrite.php` by copying and pasting commented configuration located at `config/calls.php` and make it fit your needs.


# Add API clients
In order to easily add clients to the API, a script has been created.
bin/add_client [name] [token]
Note : The name of the client must be unique, and the token should the strongest
as possible