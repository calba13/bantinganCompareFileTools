# Bantingan3
Bantingan MVC Framework

### First Time Setup
run composer update to download required library<br>

### Configuration
Open config/web.config.yml, to configure basic application settings.<br>
Open config/route.config.yml, to configure routing and path settings.<br>
Open config/database.config.yml, to configure database connection.<br>
or set value in Environment Variable (see Environment Variabel example below)

### Database
For demo purpose, please setup two database.<br>
Just create new empty database for application, then set in <b>default</b> database settings.<br>
For usermanagement demo, create new empty database for usermanagement, then run initial script that we provided in sample_usermanagement_database.sql file to create user table, set this database in <b>usermanagement</b> database settings.<br>

### Sessions in Database
For load balancing scenario, you can enable sessions in database by set Session_DB settings to true in web.config.yml.<br>
you can also change the session table name at Session_DB_Tablename settings.<br>

### Docker Image
We provided Dockerfile, for Docker image building.<br>
docker build -t image-tag-name .<br>
example :<br>
docker build -t susilon/bantingan3-app .<br><br>
Sample docker-compose also available.

### Environment Variable Example
Example of change config file value with environment variable and JSON string value :<br>
Change only SiteTitle and DefaultController in application_settings : <br>
Environment Variable Key : BANTINGAN3_APPLICATION_SETTINGS<br>
Value : { "SiteTitle":"Bantingan Docker","DefaultController":"Home"}<br>
<br>
Example of database_settings :<br>
Environment Variable Key : BANTINGAN3_DATABASE_SETTINGS<br>
Value : {"default":{"server":"db","user":"root","password":"root","database":"maindb"},"usermanagement":<br>{"type":"mysql","server":"db","user":"root","password":"root","database":"memberdb"}}<br>

### Credits
HTML template : https://github.com/flatlogic/sing-app<br>
Routing : https://github.com/symfony/routing<br>
YAML parser : https://github.com/symfony/yaml<br>
Page templating : https://github.com/smarty-php/smarty<br>
ORM : https://github.com/RedBeanPHP<br>
PDF generator : https://github.com/dompdf/dompdf<br>
Spreadsheet : https://github.com/PHPOffice/PhpSpreadsheet<br>
Mailer : https://github.com/PHPMailer/PHPMailer<br>
Captcha : https://github.com/Gregwar/Captcha<br>
Datatables : https://github.com/DataTables/DataTables<br>

TODO :<br>
Documentation.<br>
Sample of Model files."# bantinganCompareFileTools" 
