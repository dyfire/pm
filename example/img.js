var fs = require('fs');
var http = require('superagent');
var async = require('async');

var img = function(url) {
  console.log('start save img');
  async.mapLimit(url, 10, function(url, callback) {

    http.get(url.uri).end(function(err, res) {
        console.log(url.uri);
        if (err) {
          throw err;
        } else {

          fs.writeFile(url.filename, res.body, function(err) {
              if (err) {
                  throw err;
              } else {
                  console.log('save ok');
              }   
          }); 
        }   

        callback(null, 'save ok');
    }); 
  }, function(err, res) {
    if (err) {
        console.log(err);
    } else {
        console.log('finished'); 
    }   
  }); 
}

module.exports = img;
