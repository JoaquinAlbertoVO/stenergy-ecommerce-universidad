#!/usr/bin/env python3
"""
Prueba de humo del flujo de compra de ST Store (sin navegador).

Recorre por HTTP lo mismo que haría un cliente:
  producto -> agregar al carrito -> cupón -> checkout (boleta/factura) -> pago -> pedido recibido
y comprueba reglas del negocio: validación de RUC/DNI, pasarela aprobada/rechazada, Yape (en espera)
y descuento de stock. Solo usa la librería estándar de Python.

Uso:  python tools/test_purchase_flow.py http://localhost/stenergy-store
"""
import http.cookiejar
import json
import re
import sys
import urllib.error
import urllib.parse
import urllib.request

BASE = (sys.argv[1] if len(sys.argv) > 1 else "http://localhost/stenergy-store").rstrip("/")
OK, BAD = [], []


def client():
    jar = http.cookiejar.CookieJar()
    op = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
    op.addheaders = [("User-Agent", "sts-flow-test/1.0")]
    return op


def get(op, url):
    with op.open(url if url.startswith("http") else BASE + url, timeout=60) as r:
        return r.read().decode("utf-8", "replace")


def post(op, url, data, ajax=False):
    body = urllib.parse.urlencode(data, doseq=True).encode()
    req = urllib.request.Request(url if url.startswith("http") else BASE + url, data=body)
    if ajax:
        req.add_header("X-Requested-With", "XMLHttpRequest")
    try:
        with op.open(req, timeout=90) as r:
            return r.read().decode("utf-8", "replace")
    except urllib.error.HTTPError as e:
        return e.read().decode("utf-8", "replace")


def check(name, cond, extra=""):
    (OK if cond else BAD).append(name)
    print(("  [OK]  " if cond else "  [FALLA] ") + name + (f"  -> {extra}" if extra and not cond else ""))


def hidden(html, name):
    m = re.search(r'name="%s"\s+value="([^"]*)"' % re.escape(name), html) or re.search(r'value="([^"]*)"\s+name="%s"' % re.escape(name), html)
    return m.group(1) if m else ""


def product_id(html):
    m = re.search(r'name="add-to-cart"\s+value="(\d+)"', html) or re.search(r'data-product_id="(\d+)"', html)
    return m.group(1) if m else ""


def stock_text(html):
    m = re.search(r'<p class="stock[^"]*">([^<]+)</p>', html)
    return m.group(1).strip() if m else ""


def checkout_payload(html, **over):
    ship = re.search(r'name="shipping_method\[0\]"[^>]*value="([^"]+)"', html)
    data = {
        "billing_comprobante": "boleta", "billing_documento": "45678912", "billing_razon_social": "",
        "billing_first_name": "Ana", "billing_last_name": "Prueba", "billing_country": "PE", "billing_state": "LMA",
        "billing_city": "Lima", "billing_address_1": "Av. Prueba 123", "billing_address_2": "Miraflores, cerca al parque",
        "billing_postcode": "", "billing_phone": "987654321", "billing_email": "ana.prueba@example.com",
        "order_comments": "", "shipping_method[0]": ship.group(1) if ship else "",
        "payment_method": "sts_card_sim", "sts_card_number": "4242 4242 4242 4242",
        "terms": "on", "terms-field": "1",
        "woocommerce-process-checkout-nonce": hidden(html, "woocommerce-process-checkout-nonce"),
        "_wp_http_referer": "/finalizar-compra/",
    }
    data.update(over)
    return data


def add_to_cart(op, path, qty=1):
    html = get(op, path)
    pid = product_id(html)
    post(op, path, {"add-to-cart": pid, "quantity": qty})
    return pid, html


def stock_of(path):
    return stock_text(get(client(), path))


print(f"\n== ST Store · prueba del flujo de compra en {BASE} ==")

P_ED = "/producto/detector-de-tension-sin-contacto/"
P_YAPE = "/producto/reflector-led-industrial-200w-ip66/"

print("\n1) Catálogo y ficha de producto")
home = get(client(), "/")
check("La portada carga con inventario en vivo", "Inventario en vivo" in home)
check("El chatbot está en la página", 'id="sts-chatbot"' in home)
before = stock_of(P_ED)
check("La ficha muestra el stock", "unidades en stock" in before, before)
print("    stock antes:", before)

print("\n2) Carrito, cupón y checkout (boleta, tarjeta aprobada)")
op = client()
pid, _ = add_to_cart(op, P_ED, 2)
cart = get(op, "/carrito/")
check("El producto está en el carrito", "Detector de Tensión" in cart)
check("El carrito calcula IGV incluido", "IGV" in cart)
r = post(op, "/carrito/", {"coupon_code": "BIENVENIDO10", "apply_coupon": "Aplicar cupón", "woocommerce-cart-nonce": hidden(cart, "woocommerce-cart-nonce")})
check("El cupón BIENVENIDO10 se aplica", "BIENVENIDO10" in r.upper() and "coupon-bienvenido10" in r.lower(), "")
checkout = get(op, "/finalizar-compra/")
check("El checkout ofrece boleta/factura y pasarelas", "billing_comprobante" in checkout and "sts_card_sim" in checkout and "sts_yape" in checkout)
check("Hay métodos de envío para Lima", "shipping_method" in checkout)
res = json.loads(post(op, "/?wc-ajax=checkout", checkout_payload(checkout), ajax=True))
check("Pago aprobado: el pedido se crea", res.get("result") == "success", str(res)[:300])
if res.get("redirect"):
    done = get(op, res["redirect"])
    check("Página 'pedido recibido' con comprobante Boleta/DNI", "Boleta" in done and "45678912" in done)
after = stock_of(P_ED)
print("    stock después:", after)
n = lambda t: int(re.search(r"\d+", t).group()) if re.search(r"\d+", t) else -1
check("El stock bajó exactamente en 2 unidades", n(before) - n(after) == 2, f"{before} -> {after}")

print("\n3) Validaciones del checkout")
op = client(); add_to_cart(op, P_ED, 1); checkout = get(op, "/finalizar-compra/")
bad = json.loads(post(op, "/?wc-ajax=checkout", checkout_payload(checkout, billing_comprobante="factura", billing_documento="12345678901", billing_razon_social="Empresa SAC"), ajax=True))
check("RUC inválido es rechazado", bad.get("result") == "failure" and "RUC" in bad.get("messages", ""), str(bad)[:200])
bad = json.loads(post(op, "/?wc-ajax=checkout", checkout_payload(checkout, billing_documento="123"), ajax=True))
check("DNI inválido es rechazado", bad.get("result") == "failure" and "DNI" in bad.get("messages", ""))
bad = json.loads(post(op, "/?wc-ajax=checkout", checkout_payload(checkout, sts_card_number="5555 5555 5555 4444"), ajax=True))
check("Tarjeta real/no de prueba es rechazada", bad.get("result") == "failure")

print("\n4) Factura con RUC válido + pago rechazado y reintento")
fact = dict(billing_comprobante="factura", billing_documento="20100070970", billing_razon_social="Constructora Demo SAC")
bad = json.loads(post(op, "/?wc-ajax=checkout", checkout_payload(checkout, **fact, sts_card_number="4000 0000 0000 0002"), ajax=True))
check("Pago rechazado: no se crea la compra y se avisa", bad.get("result") == "failure" and "rechazado" in bad.get("messages", "").lower(), str(bad)[:200])
checkout = get(op, "/finalizar-compra/")
res = json.loads(post(op, "/?wc-ajax=checkout", checkout_payload(checkout, **fact), ajax=True))
check("Reintento con tarjeta aprobada funciona", res.get("result") == "success", str(res)[:200])
if res.get("redirect"):
    done = get(op, res["redirect"])
    check("Factura muestra RUC y razón social", "Factura" in done and "20100070970" in done and "CONSTRUCTORA DEMO SAC" in done)

print("\n5) Yape / Plin (pedido en espera, stock reservado)")
op = client(); pid, _ = add_to_cart(op, P_YAPE, 3); checkout = get(op, "/finalizar-compra/")
before = stock_of(P_YAPE)
res = json.loads(post(op, "/?wc-ajax=checkout", checkout_payload(checkout, payment_method="sts_yape", sts_yape_operacion="87654321"), ajax=True))
check("Pedido por Yape/Plin se crea", res.get("result") == "success", str(res)[:200])
after = stock_of(P_YAPE)
print(f"    stock {before} -> {after}")
check("El stock se descuenta aunque el pago esté pendiente", before != after)

print("\n6) Chatbot")
def bot(msg, ctx=None):
    body = json.dumps({"message": msg, "context": ctx or {}}).encode()
    req = urllib.request.Request(BASE + "/wp-json/stenergy-chatbot/v1/message", data=body, headers={"Content-Type": "application/json"})
    with urllib.request.urlopen(req, timeout=60) as r:
        return json.loads(r.read().decode())

def bot_text(d):
    return " ".join(m.get("text", "") for m in d["messages"] if m["type"] == "text")

d = bot("Hola"); check("Saluda", "ayudar" in bot_text(d).lower())
d = bot("¿hay stock del detector de tensión?"); t = bot_text(d); cards = [m for m in d["messages"] if m["type"] == "products"]
check("Responde stock y precio reales", "unidades" in t and "S/" in t and cards, t[:160])
d = bot("cuanto cuesta el envio a provincias"); check("Responde envíos con datos de WooCommerce", "S/ 35.00" in bot_text(d), bot_text(d)[:200])
d = bot("aceptan yape?"); check("Responde medios de pago activos", "Yape" in bot_text(d))
d = bot("necesito factura con ruc"); check("Responde comprobantes", "RUC" in bot_text(d))
d = bot("productos de iluminacion"); check("Busca por categoría", any(m["type"] == "products" for m in d["messages"]))
d = bot("quiero ver mi pedido"); check("Pide número de pedido y correo", d["context"].get("await") == "order")
d = bot("999999 nadie@example.com", d["context"]); check("No revela pedidos ajenos/inexistentes", "No encontré un pedido" in bot_text(d))
d = bot("blablabla zzz"); check("Mensaje desconocido cae en respuesta de ayuda", len(d["messages"]) >= 1)

print(f"\nResultado: {len(OK)} correctas, {len(BAD)} con fallas")
sys.exit(1 if BAD else 0)
