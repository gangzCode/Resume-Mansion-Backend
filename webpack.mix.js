const mix =require('laravel-mix')

mix.browserSync({
    proxy: "http://127.0.0.1:8001",
    host: "127.0.0.1",
    port: 8000,
    open: false
});