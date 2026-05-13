
// utils

let popupTimer = null;  

function hidePopup() {
    const popup = document.getElementById("popup_message");
    if (!popup) return;
    popup.classList.remove("visible");
    popup.classList.remove("success");
    popup.classList.remove("error");
    if (popupTimer) {
        clearTimeout(popupTimer);
        popupTimer = null;
    }
    setTimeout(() => {
        popup.style.display = "none";
    }, 300); // laisser le temps de la transition
}

function showPopup(message, isError = false) {                // Function POP-POP pour afficher les messages de succès ou d'erreur
    const popup = document.getElementById("popup_message");
    if (!popup) {
        alert(message);
        return;
    }

    popup.textContent = message;
    popup.style.display = "block";
    popup.classList.remove("success", "error");
    popup.classList.add(isError ? "error" : "success");
    popup.classList.add("visible");

    if (popupTimer) {
        clearTimeout(popupTimer);
    }

    popupTimer = setTimeout(() => {
        hidePopup();
    }, 4000);
}

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function escapeHTML(str) {

    console.log("Échappement de :", str);       // Débug pour vérifier les données avant échappement
    str = String(str);                          // S'assure que c'est une chaîne de caractères
    return str.replace(/[&<>"']/g, m => ({      // Cette fonction est utilisée pour échapper les caractères spéciaux dans les données affichées,
                                                // afin d'éviter les problèmes de sécurité liés à l'injection de code HTML ou JavaScript.
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#039;"
    })[m]);
}

document.addEventListener("DOMContentLoaded", async () => {

    const pathSegments = window.location.pathname.split('/').filter(Boolean);
    const baseRoute = pathSegments.length > 0 ? '/' + pathSegments[0] : '';
    const routePath = window.location.pathname.replace(baseRoute, '').replace(/^\/|\/$/g, '');
    const currentPage = routePath || 'home';
    const protectedPages = ['reservations'];
    const isLoginPage = currentPage === 'login';
    const isProtectedPage = protectedPages.includes(currentPage);
    const apiRoot = baseRoute;

    // 🔐 CHECK AUTH à chaque chargement de page pour protéger les pages sensibles

    try {
        const res = await fetch(`${apiRoot}/check_auth`, {
            credentials: "include"
        });

        const data = await res.json();

        // console.log(data);

        if (isLoginPage && data.logged_in) {
            window.location.href = `${baseRoute}/reservations`;
            return;
        }

        if (isProtectedPage && !data.logged_in) {
            window.location.href = `${baseRoute}/login`;
            return;
        }

        document.body.style.display = "block";
    } catch (error) {
        console.error("Erreur auth:", error);
        if (isProtectedPage) {
            window.location.href = `${baseRoute}/login`;  // En cas d'erreur (ex: serveur down), on redirige aussi vers login pour éviter de rester bloqué sur une page qui ne fonctionne pas
            return;
        }
    }


    // 🔥 Navbar dynamique qui affiche "Connecté" et "Se déconnecter"
    // si l'utilisateur est connecté, sinon redirige vers login.html

    const authSection = document.getElementById("auth_section");

    try {
        const res = await fetch(`${apiRoot}/check_auth`, {
            credentials: "include"
        });

        const data = await res.json();

        // 🔒 PROTECTION PAGE
        if (isProtectedPage && !data.logged_in) {
            window.location.href = `${baseRoute}/login`;
            return;
        }

        // 🎯 NAVBAR DYNAMIQUE
        if (authSection && data.logged_in) {
            authSection.innerHTML = `
                <ul>
                    <li><span>👀 Connecté</span></li>
                    <li><a href="#" id="logoutBtn">Se déconnecter</a></li>
                </ul>
            `;

            document.getElementById("logoutBtn").addEventListener("click", async (e) => {
                e.preventDefault();

                await fetch(`${apiRoot}/logout`, {
                    method: "POST",
                    credentials: "include"
                });

                window.location.href = `${baseRoute}/login`;
            });
        }

    } catch (error) {
        console.error("Erreur auth:", error);
        if (isProtectedPage) {
            window.location.href = `${baseRoute}/login`;
            return;
        }
    }


    console.log("JS chargé");
    console.log("loginForm=", document.getElementById("login_form"));

    // FETCH GET_RESERVATIONS pour afficher les réservations confirmées dans ma table

    fetch(`${apiRoot}/reservations/list`, { credentials: 'include' })
        .then(res => res.json())
        .then(data => {

            if (!data.success) return;

            const table = document.querySelector("#reservations_table tbody");

            data.data.sort((a, b) => {
                const dateA = new Date(a.date_reservation + " " + a.heure);
                const dateB = new Date(b.date_reservation + " " + b.heure);
                return dateA - dateB;
            });


            data.data.forEach(reservation => {

                const row = `
                    <tr>
                        <td>${escapeHTML(reservation.nom)}</td>
                        <td>${escapeHTML(reservation.prenom)}</td>
                        <td>${escapeHTML(reservation.email)}</td>
                        <td>${escapeHTML(reservation.date_reservation)}</td>
                        <td>${escapeHTML(reservation.heure)}</td>
                        <td>${escapeHTML(reservation.joueurs)}</td>
                        <td data-id="${reservation.id}">
                            <button class="edit-btn btn btn-sm btn-primary">✏️ Modifier</button>
                            <button class="delete-btn btn btn-sm btn-danger">❌ Supprimer</button>
                        </td>
                    </tr>
                `;

                if (table) table.insertAdjacentHTML("beforeend", row);
            });

        })
        .catch(err => console.error("Erreur chargement :", err));


    // FORM CONTACT  +  POP POP contact

    const contactForm = document.getElementById("contact_form");

    if (contactForm) {
        contactForm.addEventListener("submit", async function (e) {
            e.preventDefault();      // Toujours bloquer le submit pour faire les vérifications avant

            const email = contactForm.querySelector("#email").value.trim();

            // Validation

            if (!isValidEmail(email)) {
                showPopup("❌ Email invalide !", true);
                return;
            }

            const formData = new FormData(contactForm);
            showPopup("Envoi du message en cours...", false);

            try {
                const res = await fetch(`${apiRoot}/contact/send`, {
                    method: "POST",
                    body: formData
                });

                const data = await res.json();
                // console.log("RESPONSE :", data);    // Débug pour vérifier la réponse du serveur

                showPopup(data.message, !data.success);

                if (data.success) {
                    contactForm.reset();    // vide seulement les champs
                }

            } catch (error) {
                showPopup("❌ Erreur serveur", true);
            }
        });
    }


    // FORM RESERVATION

    const reservationForm = document.getElementById("reservations_form");

    if (reservationForm) {

        reservationForm.addEventListener("submit", async (e) => {
            e.preventDefault();

            console.log("Submit OK"); // 🔥 TEST

            const submitBtn = reservationForm.querySelector("button[type='submit']");

            if (submitBtn) {
                if (submitBtn.disabled) return;                  // protège même si JS lag ou double clic rapide
                submitBtn.disabled = true;                       // bloque les doubles envois
                submitBtn.textContent = "Réservation...";        // feedback utilisateur (important UX)
            }
            // Validation avant le TRY pour éviter les requêtes inutiles au serveur

            const nom = reservationForm.querySelector('[name="nom"]').value.trim();
            const prenom = reservationForm.querySelector('[name="prenom"]').value.trim();
            const email = reservationForm.querySelector('[name="email"]').value.trim();
            const date = reservationForm.querySelector('[name="date_reservation"]').value;
            const heure = reservationForm.querySelector('[name="heure"]').value;
            const joueurs = parseInt(reservationForm.querySelector('[name="joueurs"]').value) || 0;

            console.log({ nom, prenom, email, date_reservation: date, heure, joueurs });

            if (!nom || !prenom || !isValidEmail(email) || !date || !heure || joueurs <= 0) {
                showPopup("❌ Champs invalides", true);
                if (submitBtn) submitBtn.disabled = false;   // réactiver ici
                return;
            }

            try {

                const res = await fetch(`${apiRoot}/reservations/create`, {
                    method: "POST",
                    credentials: 'include',
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({ nom, prenom, email, date_reservation: date, heure, joueurs })
                });


                // 🔥 Gestion erreur HTTP

                if (!res.ok) {
                    const text = await res.text();
                    console.error("Erreur serveur :", text);
                    throw new Error("Erreur HTTP");
                }

                const data = await res.json();

                showPopup(data.message, !data.success);

                if (data.success) {
                    reservationForm.reset();

                    const table = document.querySelector("#reservations_table tbody");
                    const newId = data.id;

                    if (table) {
                        table.insertAdjacentHTML("beforeend", `
                        <tr>
                            <td>${escapeHTML(nom)}</td>
                            <td>${escapeHTML(prenom)}</td>
                            <td>${escapeHTML(email)}</td>
                            <td>${escapeHTML(date)}</td>
                            <td>${escapeHTML(heure)}</td>
                            <td>${escapeHTML(joueurs)}</td>
                            <td data-id="${newId}">
                                <button class="edit-btn btn btn-sm btn-primary">✏️ Modifier</button>
                                <button class="delete-btn btn btn-sm btn-danger">❌ Supprimer</button>
                            </td>
                        </tr>
                    `);
                    }
                }

            } catch (err) {
                console.error(err);
                showPopup("❌ Erreur serveur", true);

            } finally {
                // Toujours réactiver
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = "Réserver";
                }
            }

        });
    }

    // Lightbox

    const images = document.querySelectorAll(".gallery img");
    const lightbox = document.getElementById("lightbox");
    const lightboxImg = document.getElementById("lightbox_img");

    // console.log("Images trouvées :", images.length);
    // console.log("Lightbox :", lightbox);

    if (images.length > 0 && lightbox && lightboxImg) {

        console.log("Lightbox OK");

        // OUVRIR

        images.forEach(img => {
            img.addEventListener("click", () => {
                console.log("clic image");
                lightbox.classList.add("active");
                lightboxImg.src = img.src;
            });
        });

        // FERMER clic sur écran noir

        lightbox.addEventListener("click", (e) => {
            if (e.target === lightbox) {
                lightbox.classList.remove("active");
            }
        });

        // FERMER clic sur échap

        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape") {
                lightbox.classList.remove("active");
            }
        });
    }


    // SUPPRESSION des réservations avec le bouton "Supprimer" et confirmation avant suppression

    const tableBody = document.querySelector("#reservations_table tbody");

    if (tableBody) {
        tableBody.addEventListener("click", async (e) => {

            if (e.target.classList.contains("delete-btn")) {

                const row = e.target.closest("tr");
                const id = parseInt(row.querySelector("[data-id]")?.dataset.id);

                if (!id) {
                    showPopup("❌ ID introuvable", true);
                    return;
                }

                if (!confirm("Supprimer cette réservation ?")) return;

                try {
                    const res = await fetch(`${apiRoot}/reservations/delete`, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        credentials: 'include',
                        body: JSON.stringify({ id })
                    });

                    const data = await res.json();

                    if (data.success) {
                        row.remove();
                        showPopup("✅ Supprimé !");
                    } else {
                        showPopup("❌ Erreur suppression", true);
                    }

                } catch {
                    showPopup("❌ Erreur serveur", true);
                }
            }

            // gérer la sauvegarde après modification

            if (e.target.classList.contains("save-btn")) {

                const btn = e.target.closest(".save-btn");
                if (btn) {

                    console.log("CLICK SAVE");

                    const row = btn.closest("tr");

                    const id = parseInt(row.querySelector("[data-id]")?.dataset.id);

                    if (!id) {
                        showPopup("❌ ID introuvable", true);
                        return;
                    }

                    const inputs = row.querySelectorAll("input, select");

                    const nom = inputs[0].value.trim();
                    const prenom = inputs[1].value.trim();
                    const email = inputs[2].value.trim();
                    const date_reservation = inputs[3].value;
                    const heure = inputs[4].value;
                    const joueurs = parseInt(inputs[5].value);

                    if (!nom || !prenom || !isValidEmail(email) || !date_reservation || !heure) {
                        showPopup("❌ Champs invalides", true);
                        return;
                    }

                    try {
                        let bodyValue = JSON.stringify({ id, nom, prenom, email, date_reservation: date_reservation, heure, joueurs })
                        console.log("Body envoyé :", bodyValue); // Débug pour vérifier les données envoyées au serveur

                        const res = await fetch(`${apiRoot}/reservations/update`, {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json"
                            },
                            credentials: 'include',
                            body: bodyValue
                        });


                        const data = await res.json();

                        showPopup(data.message, !data.success);

                        if (data.success) {

                            // remettre en texte

                            row.innerHTML = `
                <td>${escapeHTML(nom)}</td>
                <td>${escapeHTML(prenom)}</td>
                <td>${escapeHTML(email)}</td>
                <td>${escapeHTML(date_reservation)}</td>
                <td>${escapeHTML(heure)}</td>
                <td>${escapeHTML(joueurs)}</td>
                <td data-id="${id}"> 
                    <div class="d-flex gap-2">
                        <button class="edit-btn btn btn-sm btn-primary">✏️ Modifier</button>
                        <button class="delete-btn btn btn-sm btn-danger">❌ Supprimer</button>
                    </div>
                </td>
            `;

                        }

                    } catch (err) {
                        console.error(err);
                        showPopup("❌ Erreur serveur", true);

                    }

                }
            }

            // Modification des réservations avec le bouton "Modifier" qui se transforme en "Sauvegarder"

            if (e.target.classList.contains("edit-btn")) {

                const row = e.target.closest("tr");

                // récupérer les cellules

                const cells = row.querySelectorAll("td");

                const nom = cells[0].textContent;
                const prenom = cells[1].textContent;
                const email = cells[2].textContent;
                const date_reservation = cells[3].textContent;
                const heure = cells[4].textContent;
                const joueurs = cells[5].textContent;

                // transformer en inputs

                cells[0].innerHTML = `<input value="${escapeHTML(nom)}">`;
                cells[1].innerHTML = `<input value="${escapeHTML(prenom)}">`;
                cells[2].innerHTML = `<input value="${escapeHTML(email)}">`;
                cells[3].innerHTML = `<input type="date" value="${escapeHTML(date_reservation)}">`;
                cells[4].innerHTML = `<input type="time" value="${escapeHTML(heure)}">`;
                cells[5].innerHTML = `
        <select>
            <option ${joueurs == 1 ? "selected" : ""}>1</option>
            <option ${joueurs == 2 ? "selected" : ""}>2</option>
            <option ${joueurs == 3 ? "selected" : ""}>3</option>
            <option ${joueurs == 4 ? "selected" : ""}>4</option>
        </select>
    `;

                // bouton devient "sauvegarder"

                e.target.textContent = "💾 Sauvegarder";
                e.target.classList.remove("edit-btn");
                e.target.classList.add("save-btn");


                // style du bouton

                e.target.classList.remove("btn-primary");
                e.target.classList.add("btn-success");
            }


        });

    }

    // 🔐 LOGIN

    const loginForm = document.getElementById("login_form");

    // console.log("CLICK LOGIN");

    if (loginForm) {
        loginForm.addEventListener("submit", async (e) => {
            e.preventDefault();

            const login_email = document.getElementById("login_email").value.trim();
            const login_password = document.getElementById("login_password").value.trim();

            console.log({ login_email, login_password });

            if (!isValidEmail(login_email) || !login_password) {
                showPopup("❌ Champs invalides", true);
                return;
            }

            try {
                const res = await fetch(`${apiRoot}/auth/login`, {
                    method: "POST",
                    credentials: 'include',
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({ login_email: login_email, login_password: login_password })
                });

                console.log("STATUS:", res.status);

                const text = await res.text();    // on récupère brut
                console.log("RESPONSE:", text);

                const data = JSON.parse(text);    // conversion manuelle

                showPopup(data.message, !data.success);

                if (data.success) {
                    window.location.href = data.redirect || `${baseRoute}/reservations`;
                }

            } catch (err) {
                console.error("ERREUR:", err);
                showPopup("❌ Erreur serveur", true);
            }
        });
    }

});


