<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — StoreManager Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0b0f19;
            --panel-bg: rgba(22, 30, 49, 0.65);
            --border-color: rgba(45, 212, 191, 0.12);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent: #2dd4bf;
            --accent-glow: rgba(45, 212, 191, 0.1);
            --success: #34d399;
            --danger: #f87171;
            --warning: #fbbf24;
            --font-family: 'Plus Jakarta Sans', sans-serif;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            font-family: var(--font-family);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 0;
            margin: 0;
            overflow-x: hidden;
        }

        .login-screen {
            position: fixed;
            inset: 0;
            background: var(--bg-color);
            z-index: 9999;
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            font-family: var(--font-family);
            color: var(--text-main);
        }

        .login-hero {
            background: linear-gradient(135deg, #0b0f19 0%, #111827 50%, #0d1b2a 100%);
            border-right: 1px solid var(--border-color);
            padding: 48px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            color: var(--text-main);
            position: relative;
            overflow: hidden;
        }

        .login-form-side {
            background: #0f1523;
            padding: 48px 64px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow-y: auto;
            color: var(--text-main);
        }

        .quick-profile-card {
            background: rgba(22, 30, 49, 0.4);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 14px;
            padding: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .quick-profile-card.active {
            background: rgba(22, 30, 49, 0.7);
            border: 2px solid var(--accent);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .form-control {
            background: rgba(8, 12, 24, 0.7);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 14px 18px;
            color: white;
            font-family: var(--font-family);
            outline: none;
            font-size: 13px;
            transition: all 0.3s;
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--accent) 0%, #0d9488 100%);
            color: #0b0f19;
            border: none;
            border-radius: 12px;
            padding: 14px 20px;
            font-weight: 800;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s;
        }
    </style>
</head>
<body>

    <div class="login-screen">
        <div class="login-hero">
            <div style="position: absolute; width: 650px; height: 650px; border-radius: 50%; border: 1px solid rgba(45, 212, 191, 0.08); bottom: -200px; left: -100px; pointer-events: none;"></div>
            <div style="position: absolute; width: 450px; height: 450px; border-radius: 50%; border: 1px solid rgba(45, 212, 191, 0.12); bottom: -100px; left: 0px; pointer-events: none;"></div>
            <div style="position: absolute; width: 250px; height: 250px; border-radius: 50%; background: radial-gradient(circle, rgba(45, 212, 191, 0.15) 0%, transparent 70%); top: 20%; right: 10%; pointer-events: none;"></div>

            <div style="display: flex; align-items: center; gap: 12px; z-index: 2;">
                <div style="background: rgba(22, 30, 49, 0.8); border: 1px solid var(--border-color); backdrop-filter: blur(12px); padding: 10px 20px; border-radius: 14px; display: flex; align-items: center; gap: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
                    <span style="font-size: 26px;">📦</span>
                    <div>
                        <div style="font-weight: 800; color: var(--accent); font-size: 17px; line-height: 1.1; letter-spacing: -0.5px;">StoreManager Pro</div>
                        <div style="font-size: 9px; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Gérez aujourd'hui, réussissez demain.</div>
                    </div>
                </div>
            </div>

            <div style="max-width: 520px; z-index: 2; margin: 60px 0;">
                <div style="display: inline-block; background: var(--accent-glow); border: 1px solid var(--accent); border-radius: 20px; padding: 6px 14px; font-size: 11px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 24px; color: var(--accent);">
                    COMMERCE • SÉNÉGAL
                </div>
                <h1 style="font-size: 42px; font-weight: 800; line-height: 1.15; margin-bottom: 20px; color: #ffffff; letter-spacing: -1px;">
                    Une boutique mieux pilotée,<br>
                    <span style="color: var(--accent); text-shadow: 0 0 20px rgba(45, 212, 191, 0.3);">une rentabilité optimisée.</span>
                </h1>
                <p style="font-size: 15px; color: var(--text-muted); line-height: 1.6; margin-bottom: 32px; font-weight: 400;">
                    Ventes, stock, dettes clients et suivi fournisseurs réunis dans un espace clair, rapide et taillé pour le commerce moderne.
                </p>

                <div style="background: rgba(22, 30, 49, 0.6); border: 1px solid var(--border-color); border-radius: 14px; padding: 14px 18px; display: flex; align-items: center; gap: 14px; width: fit-content;">
                    <div style="width: 36px; height: 36px; background: var(--accent-glow); border: 1px solid var(--accent); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--accent); font-size: 18px;">🛡️</div>
                    <div>
                        <div style="font-size: 12px; font-weight: 700; color: #ffffff;">Espace de démonstration sécurisé</div>
                        <div style="font-size: 11px; color: var(--text-muted);">Sélectionnez un profil pour tester instantanément.</div>
                    </div>
                </div>
            </div>

            <div style="font-size: 11px; color: var(--text-muted); z-index: 2;">
                Conçu pour les commerces et boutiques au Sénégal.
            </div>
        </div>

        <div class="login-form-side">
            <div style="max-width: 520px; width: 100%; margin: 0 auto;">
                
                <div style="font-size: 11px; font-weight: 800; color: var(--accent); letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 6px;">
                    RAVI DE VOUS REVOIR
                </div>
                <h2 style="font-size: 30px; font-weight: 800; color: #ffffff; margin-bottom: 8px;">
                    Connexion à StoreManager
                </h2>
                <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 28px;">
                    Choisissez un profil de démonstration ou saisissez vos identifiants.
                </p>

                <?php if (!empty($flashError)): ?>
                    <div style="background: rgba(248, 113, 113, 0.15); border: 1px solid var(--danger); color: #fca5a5; padding: 12px 16px; border-radius: 12px; font-size: 12px; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                        <span>⚠️</span> <?= is_array($flashError) ? htmlspecialchars(implode(', ', $flashError)) : htmlspecialchars($flashError) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($flashSuccess)): ?>
                    <div style="background: rgba(52, 211, 153, 0.15); border: 1px solid var(--success); color: #86efac; padding: 12px 16px; border-radius: 12px; font-size: 12px; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                        <span>✅</span> <?= is_array($flashSuccess) ? htmlspecialchars(implode(', ', $flashSuccess)) : htmlspecialchars($flashSuccess) ?>
                    </div>
                <?php endif; ?>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 24px;">
                    <div onclick="selectQuickProfile('admin', 'admin@storemanager.sn', '👑 Admin Boutique')" class="quick-profile-card active" id="profile-card-admin">
                        <div style="width: 40px; height: 40px; background: rgba(45, 212, 191, 0.15); border: 1px solid var(--accent); border-radius: 10px; font-size: 12px; font-weight: 800; color: var(--accent); display: flex; align-items: center; justify-content: center;">AB</div>
                        <div>
                            <div style="font-weight: 700; font-size: 13px; color: #ffffff;">Admin Boutique</div>
                            <div style="font-size: 10px; color: var(--text-muted);">Pilotage complet</div>
                        </div>
                    </div>

                    <div onclick="selectQuickProfile('vente', 'vente@storemanager.sn', '🛒 Chargé de Vente')" class="quick-profile-card" id="profile-card-vente">
                        <div style="width: 40px; height: 40px; background: rgba(56, 189, 248, 0.15); border: 1px solid #38bdf8; border-radius: 10px; font-size: 12px; font-weight: 800; color: #38bdf8; display: flex; align-items: center; justify-content: center;">CV</div>
                        <div>
                            <div style="font-weight: 700; font-size: 13px; color: #ffffff;">Chargé de Vente</div>
                            <div style="font-size: 10px; color: var(--text-muted);">Caisse & Dettes</div>
                        </div>
                    </div>

                    <div onclick="selectQuickProfile('stock', 'stock@storemanager.sn', '📦 Chargé de Stock')" class="quick-profile-card" id="profile-card-stock">
                        <div style="width: 40px; height: 40px; background: rgba(251, 191, 36, 0.15); border: 1px solid var(--warning); border-radius: 10px; font-size: 12px; font-weight: 800; color: var(--warning); display: flex; align-items: center; justify-content: center;">CS</div>
                        <div>
                            <div style="font-weight: 700; font-size: 13px; color: #ffffff;">Chargé de Stock</div>
                            <div style="font-size: 10px; color: var(--text-muted);">Appro & Réception</div>
                        </div>
                    </div>

                    <div onclick="selectQuickProfile('inventaire', 'inventaire@storemanager.sn', '📋 Inventaire')" class="quick-profile-card" id="profile-card-inventaire">
                        <div style="width: 40px; height: 40px; background: rgba(192, 132, 252, 0.15); border: 1px solid #c084fc; border-radius: 10px; font-size: 12px; font-weight: 800; color: #c084fc; display: flex; align-items: center; justify-content: center;">IV</div>
                        <div>
                            <div style="font-weight: 700; font-size: 13px; color: #ffffff;">Inventaire</div>
                            <div style="font-size: 10px; color: var(--text-muted);">Consultation produits</div>
                        </div>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px;">
                    <div style="flex: 1; height: 1px; background: rgba(255,255,255,0.08);"></div>
                    <div style="font-size: 11px; color: var(--text-muted); font-weight: 500;">ou avec vos identifiants</div>
                    <div style="flex: 1; height: 1px; background: rgba(255,255,255,0.08);"></div>
                </div>

                <form method="POST" action="/login" style="display: flex; flex-direction: column; gap: 16px;">
                    <input type="hidden" name="role" id="login-role-select" value="admin">

                    <div>
                        <label style="font-size: 11px; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 6px; text-transform: uppercase;">Adresse email</label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 14px;">👤</span>
                            <input type="email" name="email" id="login-email" class="form-control" value="admin@storemanager.sn" placeholder="vous@boutique.sn" style="width: 100%; padding: 12px 14px 12px 40px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 10px; color: #ffffff; font-size: 13px;" required>
                        </div>
                    </div>

                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <label style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Mot de passe</label>
                            <a href="#" onclick="alert('Mot de passe par défaut : demo1234'); return false;" style="font-size: 11px; font-weight: 600; color: var(--accent); text-decoration: none;">Mot de passe oublié ?</a>
                        </div>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 14px;">🔒</span>
                            <input type="password" name="password" id="login-password" class="form-control" value="demo1234" placeholder="Votre mot de passe" style="width: 100%; padding: 12px 40px 12px 40px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 10px; color: #ffffff; font-size: 13px;" required>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" id="remember-me" checked style="accent-color: var(--accent); width: 16px; height: 16px; cursor: pointer;">
                        <label for="remember-me" style="font-size: 12px; color: var(--text-muted); cursor: pointer;">Rester connecté sur cet appareil</label>
                    </div>

                    <button type="submit" class="btn-submit" style="padding: 14px; font-size: 14px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 8px; box-shadow: 0 10px 25px rgba(45, 212, 191, 0.25);">
                        Se connecter ➔
                    </button>
                </form>

                <div style="text-align: center; margin-top: 18px; font-size: 11px; color: var(--text-muted);">
                    ✓ Tous les comptes utilisent le mot de passe : <strong style="color: var(--accent);">demo1234</strong>
                </div>

            </div>
        </div>
    </div>

    <script>
        function selectQuickProfile(role, email, label) {
            document.querySelectorAll('.quick-profile-card').forEach(el => el.classList.remove('active'));
            const card = document.getElementById('profile-card-' + role);
            if (card) card.classList.add('active');
            document.getElementById('login-email').value = email;
            document.getElementById('login-role-select').value = role;
        }
    </script>
</body>
</html>
