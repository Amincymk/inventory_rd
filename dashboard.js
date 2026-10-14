(() => {
  "use strict";
  const KEY = "inventory-dashboard-v1",
    store = {
      get(k) {
        try {
          return localStorage.getItem(k);
        } catch {
          return null;
        }
      },
      set(k, v) {
        try {
          localStorage.setItem(k, v);
        } catch {}
      },
    };
  const seed = [
    {
      id: "p1",
      name: "Wireless Mouse",
      category: "Electronics",
      stock: 42,
      reorder: 10,
      price: 8500,
    },
    {
      id: "p2",
      name: "USB Keyboard",
      category: "Electronics",
      stock: 8,
      reorder: 12,
      price: 14500,
    },
    {
      id: "p3",
      name: "Office Chair",
      category: "Furniture",
      stock: 16,
      reorder: 5,
      price: 89000,
    },
    {
      id: "p4",
      name: "A4 Paper (Pack)",
      category: "Stationery",
      stock: 0,
      reorder: 8,
      price: 7200,
    },
  ];
  let state;
  try {
    state = JSON.parse(store.get(KEY));
  } catch {}
  if (
    !state ||
    !Array.isArray(state.products) ||
    !Array.isArray(state.movements)
  )
    state = { products: seed, movements: [] };
  const $ = (s) => document.querySelector(s),
    esc = (s) =>
      String(s ?? "").replace(
        /[&<>"']/g,
        (c) =>
          ({
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#39;",
          })[c],
      );
  const status = (p) =>
      p.stock === 0
        ? "Out of Stock"
        : p.stock <= p.reorder
          ? "Low Stock"
          : "In Stock",
    bc = { "In Stock": "b-in", "Low Stock": "b-low", "Out of Stock": "b-out" };
  let tt;
  const toast = (m) => {
    const t = $("#toast");
    t.textContent = m;
    t.classList.remove("opacity-0", "translate-y-3");
    clearTimeout(tt);
    tt = setTimeout(() => t.classList.add("opacity-0", "translate-y-3"), 2400);
  };
  const save = () => {
      store.set(KEY, JSON.stringify(state));
      render();
    },
    open = (id) => $(id).classList.add("open"),
    close = () =>
      document
        .querySelectorAll(".modal")
        .forEach((m) => m.classList.remove("open"));
  document.querySelectorAll("[data-close]").forEach((b) => (b.onclick = close));
  document.querySelectorAll(".modal").forEach(
    (m) =>
      (m.onclick = (e) => {
        if (e.target === m) close();
      }),
  );
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") close();
  });
  let pending = null;
  function render() {
    const term = $("#search").value.trim().toLowerCase(),
      cat = $("#categoryFilter").value,
      st = $("#statusFilter").value;
    const cats = [...new Set(state.products.map((p) => p.category))].sort();
    $("#categoryFilter").innerHTML =
      '<option value="">All categories</option>' +
      cats.map((c) => `<option>${esc(c)}</option>`).join("");
    $("#categoryFilter").value = cats.includes(cat) ? cat : "";
    const list = state.products.filter(
      (p) =>
        (!term || `${p.name} ${p.category}`.toLowerCase().includes(term)) &&
        (!cat || p.category === cat) &&
        (!st || status(p) === st),
    );
    $("#products").innerHTML = list.length
      ? list
          .map(
            (p) =>
              `<tr><td class="td"><b>${esc(p.name)}</b></td><td class="td">${esc(p.category)}</td><td class="td">Main Warehouse</td><td class="td">${p.stock}</td><td class="td"><span class="b ${bc[status(p)]}">${status(p)}</span></td><td class="td whitespace-nowrap"><button class="act edit" data-id="${esc(p.id)}">Edit</button><button class="act in" data-id="${esc(p.id)}">In</button><button class="act out" data-id="${esc(p.id)}">Out</button><button class="act del !text-[#f17878]" data-id="${esc(p.id)}">${pending === p.id ? "Sure?" : "Delete"}</button></td></tr>`,
          )
          .join("")
      : '<tr><td colspan="6" class="p-[25px] text-center text-mut">No matching products.</td></tr>';
    $("#resultCount").textContent =
      `(${list.length} of ${state.products.length})`;
    const units = state.products.reduce((a, p) => a + p.stock, 0),
      max = Math.max(...state.products.map((x) => x.stock), 1);
    $("#mProducts").textContent = state.products.length;
    $("#mUnits").textContent = units.toLocaleString();
    $("#mLow").textContent = state.products.filter(
      (p) => status(p) === "Low Stock",
    ).length;
    $("#mOut").textContent = state.products.filter((p) => !p.stock).length;
    $("#mValue").textContent =
      "₦" +
      state.products
        .reduce((a, p) => a + p.stock * p.price, 0)
        .toLocaleString("en-NG", { maximumFractionDigits: 2 });
    $("#chart").innerHTML = state.products.length
      ? state.products
          .slice()
          .sort((a, b) => b.stock - a.stock)
          .slice(0, 8)
          .map(
            (p) =>
              `<div class="my-2.5 grid grid-cols-[100px_1fr_42px] items-center gap-2.5 text-xs"><span title="${esc(p.name)}">${esc(p.name.length > 14 ? p.name.slice(0, 13) + "…" : p.name)}</span><div class="h-2.5 overflow-hidden rounded-[10px] bg-bar"><i class="block h-full bg-[#75a9f5]" style="width:${units ? Math.max((p.stock / max) * 100, 2) : 0}%"></i></div><b>${p.stock}</b></div>`,
          )
          .join("")
      : '<div class="p-[25px] text-center text-mut">Add products to see stock levels.</div>';
    $("#movements").innerHTML = state.movements.length
      ? state.movements
          .slice()
          .reverse()
          .slice(0, 100)
          .map(
            (m) =>
              `<tr><td class="td">${new Date(m.date).toLocaleString()}</td><td class="td">${esc(m.name)}</td><td class="td ${m.type === "In" ? "text-[#43d39e]" : "text-[#f17878]"}">${m.type}</td><td class="td">${m.type === "In" ? "+" : "−"}${m.quantity}</td></tr>`,
          )
          .join("")
      : '<tr><td colspan="4" class="p-[25px] text-center text-mut">No movements recorded.</td></tr>';
    const sel = $("#moveForm").elements.product,
      old = sel.value;
    sel.innerHTML = state.products
      .map(
        (p) =>
          `<option value="${esc(p.id)}">${esc(p.name)} (${p.stock})</option>`,
      )
      .join("");
    if (state.products.some((p) => p.id === old)) sel.value = old;
  }
  $("#search").oninput = render;
  $("#categoryFilter").onchange = render;
  $("#statusFilter").onchange = render;
  $("#clear").onclick = () => {
    $("#search").value = "";
    $("#categoryFilter").value = "";
    $("#statusFilter").value = "";
    render();
  };
  $("#add").onclick = () => {
    const f = $("#productForm");
    f.reset();
    f.elements.id.value = "";
    $("#productTitle").textContent = "Add product";
    open("#productModal");
  };
  $("#products").onclick = (e) => {
    const b = e.target.closest("button");
    if (!b) return;
    const p = state.products.find((x) => x.id === b.dataset.id);
    if (!p) return;
    if (b.classList.contains("edit")) {
      const f = $("#productForm");
      for (const k of ["id", "name", "category", "price", "stock", "reorder"])
        f.elements[k].value = p[k];
      $("#productTitle").textContent = "Edit product";
      open("#productModal");
    } else if (b.classList.contains("del")) {
      if (pending === p.id) {
        pending = null;
        state.products = state.products.filter((x) => x.id !== p.id);
        save();
        toast("Product deleted");
      } else {
        pending = p.id;
        render();
        setTimeout(() => {
          pending = null;
          render();
        }, 3000);
      }
    } else startMove(b.classList.contains("in") ? "In" : "Out", p.id);
  };
  $("#productForm").onsubmit = (e) => {
    e.preventDefault();
    const d = Object.fromEntries(new FormData(e.currentTarget));
    const old = state.products.find((p) => p.id === d.id);
    const p = {
      id: old?.id || crypto.randomUUID(),
      name: d.name.trim(),
      category: d.category.trim(),
      price: Number(d.price),
      stock: Number(d.stock),
      reorder: Number(d.reorder),
    };
    const mv = (type, q, note) =>
      state.movements.push({
        id: crypto.randomUUID(),
        productId: p.id,
        name: p.name,
        type,
        quantity: q,
        note,
        date: new Date().toISOString(),
      });
    if (old) {
      const dl = p.stock - old.stock;
      Object.assign(old, p);
      if (dl) mv(dl > 0 ? "In" : "Out", Math.abs(dl), "Edited stock");
    } else {
      state.products.push(p);
      if (p.stock) mv("In", p.stock, "Opening stock");
    }
    close();
    save();
    toast(old ? "Product updated" : "Product added");
  };
  function startMove(type, id) {
    if (!state.products.length) {
      toast("Add a product first");
      return;
    }
    $("#moveForm").reset();
    $("#moveTitle").textContent = type === "In" ? "Stock in" : "Stock out";
    $("#moveForm").dataset.type = type;
    render();
    if (id) $("#moveForm").elements.product.value = id;
    open("#moveModal");
  }
  $("#stockIn").onclick = () => startMove("In");
  $("#stockOut").onclick = () => startMove("Out");
  $("#moveForm").onsubmit = (e) => {
    e.preventDefault();
    const f = e.currentTarget,
      p = state.products.find((x) => x.id === f.elements.product.value),
      qty = Number(f.elements.quantity.value),
      type = f.dataset.type;
    if (!p || !Number.isInteger(qty) || qty < 1) return;
    if (type === "Out" && qty > p.stock) {
      toast(`Only ${p.stock} units available`);
      return;
    }
    p.stock += type === "In" ? qty : -qty;
    state.movements.push({
      id: crypto.randomUUID(),
      productId: p.id,
      name: p.name,
      type,
      quantity: qty,
      note: f.elements.note.value.trim(),
      date: new Date().toISOString(),
    });
    close();
    save();
    toast(`${qty} units recorded ${type === "In" ? "in" : "out"}`);
  };
  $("#export").onclick = () => {
    const rows = [
      [
        "Product",
        "Category",
        "Warehouse",
        "Stock",
        "Reorder level",
        "Unit price",
        "Status",
      ],
      ...state.products.map((p) => [
        p.name,
        p.category,
        "Main Warehouse",
        p.stock,
        p.reorder,
        p.price,
        status(p),
      ]),
    ];
    const csv = rows
      .map((r) =>
        r.map((v) => '"' + String(v).replaceAll('"', '""') + '"').join(","),
      )
      .join("\r\n");
    const a = document.createElement("a");
    a.href = URL.createObjectURL(new Blob([csv], { type: "text/csv" }));
    a.download = "inventory-products.csv";
    a.click();
    URL.revokeObjectURL(a.href);
  };
  const sun =
      '<svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M5 5l1.5 1.5M17.5 17.5L19 19M5 19l1.5-1.5M17.5 6.5L19 5"/></svg>',
    moon =
      '<svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>';
  const tg = $("#themeToggle");
  function applyTheme(t) {
    const l = t === "light";
    document.documentElement.classList.toggle("light", l);
    tg.innerHTML = l ? moon : sun;
    const s = l ? "Switch to dark theme" : "Switch to light theme";
    tg.setAttribute("aria-label", s);
    tg.title = s;
    store.set("inventory-theme", t);
  }
  applyTheme(store.get("inventory-theme") || "dark");
  tg.onclick = () =>
    applyTheme(
      document.documentElement.classList.contains("light") ? "dark" : "light",
    );
  render();
})();
