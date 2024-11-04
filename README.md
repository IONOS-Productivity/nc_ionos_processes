# Ionos Processes

This addon handles IONOS internal processes like customized mail delivery upon various events.

## Build

```
nextcloud $ composer install --prefer-dist
```

## Install/enable

```
nextcloud $ ./occ app:enable nc_ionos_processes
```

## Configure

In order to be able to consume API you will need to set the following configuration values:

* `ionos_mail_base_url` - base URL of the API

```shell
./occ config:app:set --value 'https://api.example.lan:10443/easynextcloud-mail-notification' --type string nc_ionos_processes ionos_mail_base_url
```

* `basic_auth_user` - API user ID

```shell
./occ config:app:set --value '<API_USER_ID>' --type string nc_ionos_processes basic_auth_user
```

* `basic_auth_pass` - API user password

```shell
./occ config:app:set --value '<API_USER_PASS>' --type string nc_ionos_processes basic_auth_pass
```

* `allow_insecure` - (optional) allow insecure connections like self-signed certificates

```shell
./occ config:app:set --value true --type boolean nc_ionos_processes allow_insecure
```
