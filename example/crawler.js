var http = require('superagent');
var jq = require('cheerio');
var url = require('url');
var proxy = require('eventproxy');
var q = require('q');
var fs = require('fs');
var async = require('async');
var headers = require('./cookie.json');
var base_url = 'https://www.zhihu.com';
var save = require('./img.js');

var uri = { 
    'uri' : 'https://www.zhihu.com/',
    'login_uri' : 'https://www.zhihu.com/login/email',
    'target_uri' : 'https://www.zhihu.com/collections'
};

var look = function(url) {
    console.log('start init page >>>');
    var defer = q.defer();
     http.get(url).set(headers).end(function(err, res) {
      if (!err) {
        var o = JSON.parse(res.text);
        var arr = o[0];
        var url = []; 
        var base = base_url + '/question/';
        for (var i = 0; i < arr.length; i++) {
            var t = arr[i];
            if (t[0] == 'question') {
                url.push(base + t[3]);
            }   
        }   

        console.log('search result page'+ url.length);
        defer.resolve(url);
      } else {
        defer.reject(err);
      }   
    
    }); 

      return defer.promise;
};

var find = function(url) {
    console.log('start batch get list page >>> ');
    var defer = q.defer();

    async.mapLimit(url, 5, function(uri, callback) {
          http.get(uri).set(headers).end(function(err, res) {

        if (err) {
            derer.reject(err);
        }

          var data = [];
          var $ = jq.load(res.text);
          $('.zm-item-answer.zm-item-expanded').each(function(i, el) {
            var e = $(el);
            var str = e.html();
            var dt = {
              'nickname' : jq('.author-link', str).text(),
              'icon' : jq('img', str).attr('src'),
              'title' : jq('.bio', str).text(),
              'content' : jq('.zh-summary.summary.clearfix', str).text(),
              'des' : jq('.zh-summary.summary.clearfix > a',str).attr('href')
            };
            data.push(dt);
          });
          callback(null, data);
      });
    }, function(err, res) {
        if (!err) {
          console.log('the result list '+ res.length);
            defer.resolve(res);
        } else {
            defer.reject(err);
        }
    });

    return defer.promise;
};

var detail = function(uri) {
    console.log('start get detail page >>>');
    var defer = q.defer();
    var url = [];
    var t = null;
    var patter = null;
    for (var i = 0; i < uri.length; i++) {
        t = uri[i];
        for (var j = 0; j < t.length; j++) {
          if (t[j].des !== undefined) {
                if (t[j].des.indexOf('http') < 0) {
                    url.push(base_url + t[j].des);
                }
          }
        }
               }
    async.mapLimit(url, 5, function(uri, callback){
        console.log(uri);
        http.get(uri).set(headers).end(function(err, res) {
            var data = [];
            if (err) {
                defer.reject(err);
                console.log(err);
            } else {
                var $ = jq.load(res.text);
                $('.zm-item-answer.zm-item-expanded  img').each(function(i, el) {
                    var e = $(el);

                    if (e.attr('src').indexOf('whitedot') < 0) {
                        data.push({
                          "uri" : e.attr('src'),
                          "filename" : "img/" + e.attr('src').substr(e.attr('src').lastIndexOf('/') + 1)
                        });
                    }
                });
            }

            callback(null, data);
        });
    }, function(err, res) {
        if (err) {
            defer.reject(err);
            console.log(err);
        } else {
            var data = [];
            var tm = null;
            for (var i = 0;i < res.length; i++) {
                tm = res[i];
                for (var j = 0;j < tm.length; j++) {
                    data.push(tm[j]);
                }
            }

            defer.resolve(data);
        }
    });

    return defer.promise;
};

var words = '装修背景墙';
var url = 'https://www.zhihu.com/autocomplete?max_matches=10&use_similar=0&token='+ encodeURI(words);
look(url).then(find, function(err) { console.log(err);}).then(detail, console.error).then(save, function(err) { console.log('hhh');console.log(err);}) ;

console.log('finished');
