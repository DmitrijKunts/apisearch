# Выдача Google через API
С помощью сервиса можно отправлять запросы к поисковым системам Google и получать ответы в формате Яндекс.XML.

Аналог 
* https://xmlriver.com/
* https://xmlstock.com/ 

Но только для Google, использует Custom Search API  https://support.google.com/programmable-search/


Файл с аккаунтами должен быть размещен в storage/app/accs.txt
```
9dbf987ad219697b3   AIzaSyB2XqNkG55oEt887h2fDJz8sMRIgVXKhEY
3a19230121b0d0b50f   AIzaSyC0l2ZUJhnNQ679Sur1hsuVSHk2iWaCutM
2ca57e67f7308de96   AIzaSyAs_idPB9QYGccRwiFhdKw983Zd1lA1KoY
......
```
Инструкция как регистрировать аккаунты https://mixedanalytics.com/blog/seo-data-google-custom-search-json-api/

В .env нужно указать API_KEY для доступа из стороннего софта, например KeyCollector.

Пример настройки KeyCollector для работы через этот сервис

<img src="https://codelockerlab.com/github/kcapisample.png" width="400">
