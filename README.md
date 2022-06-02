# PHP Apache access.log reader
Returns number of lines (total, good/valid, bad/invalid), total hits, total traffic in bytes, 
total unique paths, total statuses with number of occurrences, number of known and unknown bots.
<br/><br/>
Exmple return **$data**:<br/>
```
{
    "lines": {
        "total": 46712,
        "good": 46712,
        "bad": 0
    },
    "hits": 46712,
    "bytes": 2408376398,
    "paths": 3542,
    "statuses": {
        "200": 40917,
        "404": 3447,
        "303": 1147,
        "301": 955,
        "206": 97,
        "403": 61,
        "400": 1,
        "304": 87
    },
    "bots": {
        "known": {
            "Bing": 69,
            "Google": 102,
            "Yandex": 6
        },
        "unknown": {
            "\"Mozilla\/5.0 (compatible; DotBot\/1.1; http:\/\/www.opensiteexplorer.org\/dotbot, help@moz.com)\" \"-\"": 1,
            "\"Mozilla\/5.0 (compatible; AhrefsBot\/7.0; +http:\/\/ahrefs.com\/robot\/)\" \"-\"": 48,
            "\"Mozilla\/5.0 (compatible; Discordbot\/2.0; +https:\/\/discordapp.com)\" \"-\"": 1,
            "\"Mozilla\/5.0 (compatible; Seekport Crawler; http:\/\/seekport.com\/\" \"-\"": 4,
            "\"Mozilla\/5.0 (compatible; MJ12bot\/v1.4.8; http:\/\/mj12bot.com\/)\" \"-\"": 40,
            "\"Mozilla\/5.0 (compatible; Nimbostratus-Bot\/v1.3.2; http:\/\/cloudsystemnetworks.com)\" \"-\"": 8,
            "\"Mozilla\/5.0 (Macintosh; Intel Mac OS X 10_15_5) AppleWebKit\/605.1.15 (KHTML, like Gecko) Version\/13.1.1 Safari\/605.1.15 (Applebot\/0.1; +http:\/\/www.apple.com\/go\/applebot)\" \"-\"": 28,
            "\"Mozilla\/5.0(Linux;Android8.1.0;CUBOT_POWER)AppleWebKit\/537.36(KHTML,likeGecko)Chrome\/85.0.4183.81MobileSafari\/537.36\" \"-\"": 20,
            "\"Mozilla\/5.0 (Windows NT 6.1; WOW64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/50.4.2661.102 Safari\/537.36; 360Spider\" \"-\"": 17,
            "\"Mozilla\/5.0 (compatible; Seekport Crawler; http:\/\/seekport.com\/)\" \"-\"": 16,
            "\"Mozilla\/5.0 (compatible; Adsbot\/3.1)\" \"-\"": 19,
            "\"Mozilla\/5.0 (compatible; SEOkicks; +https:\/\/www.seokicks.de\/robot.html)\" \"-\"": 34,
            "\"Mozilla\/5.0 (Macintosh; Intel Mac OS X 10_7_3) AppleWebKit\/[WEBKIT_VERSION] (KHTML, like Gecko, Mediapartners-Google) Chrome\/[CHROME_VERSION] Safari\/[WEBKIT_VERSION]\" \"-\"": 11,
            "\"Mozilla\/5.0 (compatible; Linux x86_64; Mail.RU_Bot\/2.0; +http:\/\/go.mail.ru\/help\/robots)\" \"-\"": 2,
            "\"RyteBot\/1.0.0 (+https:\/\/bot.ryte.com\/)\" \"-\"": 1,
            "\"Mozilla\/5.0 (compatible; at-bot\/1.0; +https:\/\/crawler.labs.nic.at)\" \"-\"": 2,
            "\"Gigabot\/3.0 (http:\/\/www.gigablast.com\/spider.html)\" \"-\"": 2,
            "\"Sogou web spider\/4.0(+http:\/\/www.sogou.com\/docs\/help\/webmasters.htm#07)\" \"-\"": 8,
            "\"Screaming Frog SEO Spider\/10.1\" \"-\"": 2,
            "\"Slackbot-LinkExpanding 1.0 (+https:\/\/api.slack.com\/robots)\" \"-\"": 34,
            "\"Slackbot 1.0 (+https:\/\/api.slack.com\/robots)\" \"-\"": 2,
            "\"TelegramBot (like TwitterBot)\" \"-\"": 1,
            "\"Mozilla\/5.0 (compatible; MetaJobBot; https:\/\/www.metajob.at\/crawler)\" \"-\"": 2,
            "\"Mozilla\/5.0 (compatible; SemrushBot-SI\/0.97; +http:\/\/www.semrush.com\/bot.html)\" \"-\"": 1,
            "\"Mozilla\/5.0 (compatible; Barkrowler\/0.9; +https:\/\/babbar.tech\/crawler)\" \"-\"": 19,
            "\"Mozilla\/5.0 (compatible; intelx.io_bot +https:\/\/intelx.io)\" \"-\"": 5,
            "\"Mozilla\/5.0 (compatible; Pinterestbot\/1.0; +http:\/\/www.pinterest.com\/bot.html)\" \"-\"": 4,
            "\"Mozilla\/5.0 (Windows NT 6.1; WOW64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/50.0.2661.102 Safari\/537.36; 360Spider\" \"-\"": 1,
            "\"RyteBot\/1.0.0 (Linux; Android 8.0.0; Nexus 5X Build\/OPR4.170623.006) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/87.0.4280.88 Mobile Safari\/537.36 (+https:\/\/bot.ryte.com\/)\" \"-\"": 4,
            "\"Mozilla\/5.0 (compatible; 008\/0.83; http:\/\/www.80legs.com\/webcrawler.html) Gecko\/2008032620\" \"-\"": 1680,
            "\"Mozilla\/5.0 (compatible; MojeekBot\/0.10; +https:\/\/www.mojeek.com\/bot.html)\" \"-\"": 2,
            "\"Mozilla\/5.0 (compatible; adscanner\/)\/1.0 (Mozilla\/5.0 (compatible; seoscanners.net\/1.0; +spider@seoscanners.net); http:\/\/seoscanners.net; spider@seoscanners.net)\" \"-\"": 132
        }
    }
}
```
The script was tested on 2GB access.log file.<br/><br/>
To start to use the script simply require ReaderLog class and see how to use it in **script_prod.php**.<br/><br/>
There is 2 functions to parse log files:<br/> 
**getLogHeavy()** divides big file into smaller parts so it's saver for memory;<br/>
**getLogLight()** does its job without separating the log file. It affects onto memory usage.<br/><br/>
Usage:
```
// getLogHeavy()
$lr->assignLogFileName('access2_log'); // log file path
if (!$lr->splitLogFile()) { print 'Could not find splitted log files.'; return; } // trying to split log file into pieces
$data = $lr->getLogHeavy(); // getting output info
```
```
// getLogLight()
$lr->assignLogFileName('access2_log'); // log file path
$data = $lr->getLogLight(); // getting output info
```

