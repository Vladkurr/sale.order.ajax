# Bitrix js form component

__Component calling example for default template:__
```php
<?$APPLICATION->IncludeComponent(
	"sale:order",
	"",
	Array(
		"DELIVERIES" => array("1","2","3"),
		"PAYSYSTEMS" => array("1","3","4"),
		"PERSONS" => array("1","2")
	)
);?>
```

