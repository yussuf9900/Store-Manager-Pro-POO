    </div>
    <script>
        function toggleDetails(panelId) {
            const panel = document.getElementById(panelId);
            if (!panel) return;
            const isVisible = window.getComputedStyle(panel).display !== 'none';
            panel.style.display = isVisible ? 'none' : 'block';

            const parentRow = panel.closest('tr');
            if (parentRow) {
                const drawers = Array.from(parentRow.querySelectorAll('.details-drawer'));
                const anyOpen = drawers.some(dr => window.getComputedStyle(dr).display !== 'none');
                parentRow.style.display = anyOpen ? '' : 'none';
            }
        }

        function initPaginatedTable(tableId, rowsPerPage = 10) {
            const table = document.getElementById(tableId);
            if (!table) return;

            const tbody = table.querySelector("tbody");
            if (!tbody) return;

            const allRows = Array.from(tbody.children);
            const groups = [];
            for (let i = 0; i < allRows.length; i++) {
                const r = allRows[i];
                const cells = r.querySelectorAll("td");
                if (cells.length > 0 && cells[0].getAttribute("colspan") === null) {
                    const nextRow = allRows[i + 1];
                    const hasDetail = nextRow && nextRow.querySelector(".details-drawer");
                    groups.push({
                        main: r,
                        detail: hasDetail ? nextRow : null
                    });
                }
            }

            let pagerContainer = document.getElementById(tableId + "-pager");
            if (!pagerContainer) {
                pagerContainer = document.createElement("div");
                pagerContainer.id = tableId + "-pager";
                pagerContainer.style.display = "flex";
                pagerContainer.style.justifyContent = "center";
                pagerContainer.style.alignItems = "center";
                pagerContainer.style.gap = "8px";
                pagerContainer.style.marginTop = "16px";
                pagerContainer.style.padding = "10px 0";
                table.parentNode.insertBefore(pagerContainer, table.nextSibling);
            }

            table.updatePagination = function() {
                const activeGroups = groups.filter(g => g.main.style.display !== "none");
                const totalPages = Math.ceil(activeGroups.length / rowsPerPage);
                let currentPage = 1;

                function showPage(page) {
                    if (page < 1) page = 1;
                    if (page > totalPages) page = totalPages;
                    currentPage = page;
                    
                    const start = (page - 1) * rowsPerPage;
                    const end = start + rowsPerPage;

                    activeGroups.forEach((group, idx) => {
                        const inRange = idx >= start && idx < end;
                        group.main.style.setProperty("display", inRange ? "" : "none", "important");
                        if (group.detail) {
                            if (!inRange) {
                                group.detail.style.display = "none";
                            } else {
                                const drawers = Array.from(group.detail.querySelectorAll(".details-drawer"));
                                const drawerVisible = drawers.some(dr => window.getComputedStyle(dr).display !== 'none');
                                group.detail.style.display = drawerVisible ? "" : "none";
                            }
                        }
                    });

                    renderPager();
                }

                function renderPager() {
                    pagerContainer.innerHTML = "";
                    if (totalPages <= 1) {
                        pagerContainer.style.display = "none";
                        return;
                    }
                    pagerContainer.style.display = "flex";

                    const prevBtn = document.createElement("button");
                    prevBtn.className = "btn-quick-action";
                    prevBtn.innerText = "◀";
                    prevBtn.disabled = currentPage === 1;
                    prevBtn.style.opacity = currentPage === 1 ? "0.4" : "1";
                    prevBtn.onclick = (e) => { e.preventDefault(); if (currentPage > 1) showPage(currentPage - 1); };
                    pagerContainer.appendChild(prevBtn);

                    let startPage = Math.max(1, currentPage - 2);
                    let endPage = Math.min(totalPages, startPage + 4);
                    if (endPage - startPage < 4) {
                        startPage = Math.max(1, endPage - 4);
                    }

                    for (let i = startPage; i <= endPage; i++) {
                        const pageBtn = document.createElement("button");
                        pageBtn.className = "btn-quick-action";
                        pageBtn.innerText = i;
                        pageBtn.style.minWidth = "30px";
                        if (i === currentPage) {
                            pageBtn.style.background = "var(--accent)";
                            pageBtn.style.borderColor = "var(--accent)";
                            pageBtn.style.color = "white";
                        }
                        pageBtn.onclick = (e) => { e.preventDefault(); showPage(i); };
                        pagerContainer.appendChild(pageBtn);
                    }

                    const nextBtn = document.createElement("button");
                    nextBtn.className = "btn-quick-action";
                    nextBtn.innerText = "▶";
                    nextBtn.disabled = currentPage === totalPages;
                    nextBtn.style.opacity = currentPage === totalPages ? "0.4" : "1";
                    nextBtn.onclick = (e) => { e.preventDefault(); if (currentPage < totalPages) showPage(currentPage + 1); };
                    pagerContainer.appendChild(nextBtn);
                }

                showPage(1);
            };

            table.updatePagination();
        }

        document.addEventListener("DOMContentLoaded", () => {
            const toast = document.getElementById("main-toast");
            if (toast) {
                setTimeout(() => {
                    toast.style.animation = "slideIn 0.3s ease reverse forwards";
                    setTimeout(() => toast.remove(), 300);
                }, 4000);
            }
        });
    </script>
</body>
</html>
