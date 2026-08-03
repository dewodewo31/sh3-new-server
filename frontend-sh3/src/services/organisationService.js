import api from "./api";

// Konversi node struktur dari backend ({name, position, children, period_*})
// menjadi bentuk yang dipakai halaman struktur ({position_name, holders, children}).
function mapNode(node) {
    const period_text =
        node.period_start || node.period_end
            ? `${node.period_start || "?"} s/d ${node.period_end || "?"}`
            : null;

    return {
        id: node.id,
        position_name: node.position,
        holders: [{ name: node.name, period_text }],
        children: (node.children || []).map(mapNode),
    };
}

export const organisationService = {
    getTree: (year) =>
        api.get("/organization/tree", { params: year ? { year } : {} }).then((res) => ({
            ...res,
            data: {
                tree: (res.data.data || []).map(mapNode),
            },
        })),
    getYears: () => api.get("/organization/years"),
};
