const express = require('express');
const app = express();
const path = require('path');

// Esto le dice al servidor: "Todo lo que esté en la carpeta 'public' es para que lo vea la gente"
app.use(express.static(path.join(__dirname, 'public')));

// Arrancamos el servidor en el puerto 3000
const PORT = 3000;
app.listen(PORT, () => {
    console.log(`✅ Servidor funcionando en: http://localhost:${PORT}`);
    console.log(`💡 Entra a esa dirección para ver tu web con Tawk.to`);
});