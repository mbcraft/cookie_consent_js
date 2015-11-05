# Italiano

### Overview

Questo pacchetto software è composta da tre parti : la gestione dei cookie compreso il banner e la tabella per la modifica
delle preferenze, il riconoscimento automatico degli utenti non automatici (human,js) e una parte server
scritta in php che effettua il logging delle azioni relative ai cookie. ('log_cookies.php' e cartella 'cookies')
Nella cartella test è possibile trovare degli esempi funzionanti delle funzionalità di questa libreria.

NOTA : alcune funzionalità in locale negli esempi non funzionano con Internet Explorer. La libreria
dovrebbe essere multi browser quando utilizzata online.

## Cookies

La libreria dei cookie può essere utilizzata per gestire i cookies e la cookie policy per i siti web.
È necessario un server web php per la parte relativa al logging.
Ha bisogno del css cookies.css per il layout del banner e della tabella delle preferenze, che 
può essere caricato utilizzando un tag <link> nell'header della pagina.

In pratica, ci sono varie applicazioni >javascript< che possono utilizzare i cookie. Ogni applicazione è composta
da javascript, css e parti di html che possono essere OPZIONALMENTE caricate dal server web (in base
alle preferenze dei cookie). Quindi, una volta caricata la libreria nell'header della pagina, all'inizio
della pagina si utilizza un tag <script> per dichiarare e configurare tutte le 'applicazioni javascript che 
utilizzano cookie'.
Si dichiarando solamente quelle che utilizzano cookie non tecnici, siccome quelli tecnici sono
necessari per il funzionamento del sito.

es:
`
cookies.setupApplicationCookies("an_app", "Descrizione esempio.",[{id:"js_l_1",path:"js/jslib.js",type:"js"},{id:"css_1",type:"css",path:"css/my.css"},{id:"an_app_html_placeholder",type:"html",path:"/test/html/part.html"},{id:"js_l_2",path:"js/jslib2.js",type:"js"}]);
cookies.setupApplicationCookies("my_cookie_app", "Questa è una descrizione della mia cookie app.",[{id:"css_2",type:"css",path:"css/my.css"}]);
cookies.setupApplicationCookies("another_app", "Un'altra app con un'altra descrizione.",[{id:"js_l_3",path:"js/jslib.js",type:"js"}]);
`
Il metodo 'cookies.setupApplicationCookies' vuole 3 parametri.
Il primo è un identificatore univoco dell'applicazione. È anche mostrato nella tabella delle
preferenze dei cookie. Il secondo è la descrizione dell'applicazione. Anch'essa è utilizzata
nella tabella delle preferenze. Il terzo parametro è un array di 'specifiche di caricamento' (può essere vuoto).
Ogni specifica è un oggetto {} con i seguenti campi : 

id -> id stringa della specifica di caricamento. Se il tipo è 'html' è anche l'id dell'elemento html in cui inserire il contenuto caricato.
type -> può essere 'js', 'css' o 'html'. Se 'js' o 'css' la risorsa è aggiunta all'intestazione della pagina, altrimenti è inserita nell'elemento della pagina con id uguale all'id della specifica di caricamento.
path -> il percorso della risorsa da caricare. La risorsa è caricata in modo sincrono con una richiesta GET.
(media) -> se il tipo è 'css' è anche possibile aggiungere un campo 'media' che viene aggiunto come attributo al tag link inserito nello header.

Non è necessario fornire delle specifiche di caricamento : se viene fatto, le risorse sono caricate in questo modo. 
Se un file js o css è utilizzato da più applicazioni, viene caricato una sola volta (se ha lo stesso path).
Si è liberi di caricare le risorse in altro modo (es. usando jquery). Consiglio di inserire tutte le chiamate a setupApplicationCookies
subito dopo l'apertura del tag <body>.

Per controllare quale 'applicazione che utilizza cookie' è abilitata, è possibile invocare il metodo :

`cookies.getEnabledApplicationCookies()`

ritorna un array di 'cookie application id' abilitate. (il primo parametro del metodo cookies.setupApplicationCookies).

Per visualizzare il banner che mostra la cookie policy breve, è possibile utilizzare il metodo :
`
cookies.showCookieBanner("Html della cookie policy breve qui", "etichetta del pulsante accetto qui");
`     

Prende due parametri : il primo è l'html della cookie policy breve, il secondo è il testo del pulsante 'Accetto'.
Quando viene chiamato questo metodo, il banner è mostrato soltanto se :

- la tabella per le preferenze dei cookie non è stata visualizzata in questa pagina
- i cookie non sono già stati accettati

Inoltre, se i cookie sono già stati accettati, tutte le 'applicazioni che usano cookies' sono caricate
nell'ordine in cui sono configurate. Quindi è possibile inserire questo metodo in fondo ad ogni pagina.

Inoltre, è possibile visualizzare la tabella per modificare le preferenze di utilizzo dei cookie col metodo :

`
cookies.showCookiePreferencesTable("enabled_label","application_label","description_label");
`

Prende tre parametri :

- etichetta per la colonna 'abilitata'.
- etichetta per la colonna 'application id'.
- etichetta per la colonna 'description'.

Questo consente la localizzazione della tabella.
La tabella avrà tre colonne : nella prima sarà presente una check box che consentirà all'utente di
abilitare/disabilitare un'applicazione. Quando viene visualizzata la tabella delle preferenze, il banner
per l'accettazione dei cookie non viene mostrato (se la chiamata è successiva a questa) e quindi le
applicazioni che utilizzano i cookie non vengono caricate automaticamente.

Per forzare il caricamento automatico, è possibile chiamare :
`
cookies.loadEnabledApplications();
`

La parte server (cartella 'cookies') deve essere copiata in una apposita cartella, e il file log_cookies.php deve essere
copiato nella root del sito. Nel caso la cartella venga rinominata, è necessario modificare la direttiva
include presente nel file log_cookies.php. La cartella root del sito deve avere i permessi
di scrittura per il php in quanto il logger su file effettivamente crea file e cartelle.
Se i nomi delle cartelle non vengono modificati le impostazioni di default dovrebbero
permette al sistema di logging di funzionare correttamente. 
Al momento solo il logging su file è supportato. È possibile implementare altre tipi di logging creando una classe che implementa l'interfaccia ICookieLogDriver.


## HumanJS

La seconda parte della libreria verifica se lo user agent è effettivamente un umano (non-bot).
Per farlo utilizza alcuni eventi come il movimento del mouse, click (sia normale che doppio), pressione di tasti, scroll e tempo trascorso calcolando un punteggio combinato.
Dopo che l'utente è stato riconosciuto (il punteggio supera un valore prefissato) come umano tutti i listener degli eventi sono rimossi.
Legge inoltre le dimensioni dello schermo che vengono salvate nei cookies
('humanjs_screen_width' e 'humanjs_screen_height') e possono essere utilizzate sia client-side che
 server-side della pagina successiva. Se l'utente è verificato viene scritto il cookie 'humanjs_status' col valore 'verified'.

Per utilizzare la libreria è necessario includerla nella pagina (usando il tag *script*, ad esempio) :

`<script type='text/javascript' src='/js/human.js'></script>`

mentre le chiamate possono essere inserito in un tag *script* inserito successivamente, ad esempio :

`
<form name="my_form" method="POST" action="">
    <!-- 
    ...
    etichette e campi della form 
    ...
    -->
    <!-- il pulsante di submit è nascosto, viene mostrato quando un utente viene riconosciuto come umano -->
    <input id='submit_button' style="display:hidden;" type='submit' name="Invia" value="Invia" />
</form>
<script type='text/javascript'>

        humanjs.setup(function() {
            document.getElementById("submit_button").style.display = "block";
        }, false);

</script>
`

Nell'esempio precedente il pulsante 'Invia' della form viene mostrato solo quando l'utente viene effettivamente riconosciuto come umano dalla libreria.
Una strategia migliore potrebbe essere quella di impostare l'attributo _action_ della form a un valore valido.




## Metodi disponibili :

`humanjs.setup(callback,forceCallback);`

Aggancia i listener per il riconoscimento dell'utente al documento corrente. (comportamento di default)

#### Parametri :

* *callback* : funzione da chiamare una volta riconosciuto l'utente come umano. Se _null_ nessuna chiamata viene effettuata (ma l'utente viene comunque registrato come verificato).
* *forceCallback* : se _true_ esegue la callback se l'utente è già verificato durante il setup.



`humanjs.setupWithId(element_id,callback,forceCallback);`

##### Parametri :

* *element_id* : L'id dell'elemento DOM in cui registrare il tracking del mouse.
* *callback* : funzione da chiamare una volta riconosciuto l'utente come umano. Se _null_ nessuna chiamata viene effettuata (ma l'utente viene comunque registrato come verificato).
* *forceCallback* : se _true_ esegue la callback se l'utente è già verificato durante il setup.

 

`humanjs.setupWithElement(element,callback,forcecallback);`
 
##### Parametri :

* *element* : L'elemento DOM in cui registrare il tracking del mouse.
* *callback* : funzione da chiamare una volta riconosciuto l'utente come umano. Se _null_ nessuna chiamata viene effettuata (ma l'utente viene comunque registrato come verificato).
* *forceCallback* : se _true_ esegue la callback se l'utente è già verificato durante il setup.



`humanjs.isVerified();`

Ritorna true se l'utente è stato verificato come umano, falso se la verifica non è ancora avvenuta.



`humanjs.resetVerified();`

Resetta lo stato a utente umano non verificato.
Una volta verificato un utente come umano tutti i listener sono rimossi.
I valori all'interno della libreria sono settati a valori ragionevoli.


## Licenza

Vedere le condizioni di licenza nella cartella docs/license all'interno di questo pacchetto.


## Crediti

Alcune parti da Cookies.js (inclusa in human.js) - un GRANDE grazie a https://github.com/ScottHamper/Cookies/blob/master/src/cookies.js

Copyright by : MBCRAFT di Marco Bagnaresi - http://www.mbcraft.it



