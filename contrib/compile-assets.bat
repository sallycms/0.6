@echo off
call uglifyjs -nc -o ../sally/backend/assets/js/standard.min.js ../sally/backend/assets/js/standard.js
call uglifyjs -nc -o ../sally/backend/assets/js/jquery.timepicker.min.js ../sally/backend/assets/js/jquery.timepicker.js
call uglifyjs -nc -o ../sally/backend/assets/js/jquery.imgcheckbox.min.js ../sally/backend/assets/js/jquery.imgcheckbox.js
