// Posiciones de los puntos en una cuadrícula 3x3 para cada cara del dado
const CARAS = {
  1: [5],
  2: [1, 9],
  3: [1, 5, 9],
  4: [1, 3, 7, 9],
  5: [1, 3, 5, 7, 9],
  6: [1, 3, 4, 6, 7, 9]
};

function pintarDado(dado, valor) {
  dado.innerHTML = "";
  for (let i = 1; i <= 9; i++) {
    const punto = document.createElement("i");
    if (CARAS[valor].includes(i)) punto.className = "on";
    dado.appendChild(punto);
  }
  dado.dataset.valor = valor;
  dado.setAttribute("aria-label", "Dado con valor " + valor);
}

// Marca el enlace de la página actual en el menú
const pagina = location.pathname.split("/").pop() || "index.html";
document.querySelectorAll("nav a").forEach(enlace => {
  if (enlace.getAttribute("href") === pagina) enlace.setAttribute("aria-current", "page");
});

// Dados: el del logo y los de la mesa de la portada
document.querySelectorAll(".logo-dado, .dado").forEach(dado => {
  pintarDado(dado, Number(dado.dataset.valor) || 6);
});

const botonTirar = document.getElementById("tirar");
if (botonTirar) {
  const dados = document.querySelectorAll(".dado");
  const resultado = document.getElementById("tirada");
  botonTirar.addEventListener("click", () => {
    let total = 0;
    dados.forEach(dado => {
      const valor = Math.floor(Math.random() * 6) + 1;
      total += valor;
      dado.classList.remove("rodando");
      void dado.offsetWidth; // reinicia la animación
      dado.classList.add("rodando");
      pintarDado(dado, valor);
    });
    resultado.textContent = total === 12 ? "¡Doble seis! Total: 12 🎉" : "Has sacado un " + total;
  });
}

// Días que faltan para cada actividad
document.querySelectorAll("[data-fecha]").forEach(tarjeta => {
  const dias = Math.ceil((new Date(tarjeta.dataset.fecha) - new Date()) / 86400000);
  const cuenta = tarjeta.querySelector(".cuenta");
  if (!cuenta) return;
  if (dias > 1) cuenta.textContent = "Faltan " + dias + " días";
  else if (dias === 1) cuenta.textContent = "¡Mañana!";
  else if (dias === 0) cuenta.textContent = "¡Hoy!";
  else cuenta.textContent = "Finalizada";
});

// Animación de entrada al hacer scroll
const elementos = document.querySelectorAll(".panel, article, .titulo-pagina");
if ("IntersectionObserver" in window) {
  const observador = new IntersectionObserver(entradas => {
    entradas.forEach(entrada => {
      if (entrada.isIntersecting) {
        entrada.target.classList.add("visible");
        observador.unobserve(entrada.target);
      }
    });
  }, { threshold: 0.1 });
  elementos.forEach(el => {
    el.classList.add("aparece");
    observador.observe(el);
  });
}
