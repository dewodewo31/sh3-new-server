import { Document, Page, Text, View, Image, StyleSheet } from "@react-pdf/renderer"

const styles = StyleSheet.create({
    page: {
        padding: 40,
        backgroundColor: "#ffffff",
        fontFamily: "Helvetica",
    },
    logoContainer: {
        alignItems: "center",
        marginBottom: 16,
    },
    logo: {
        width: 80,
        height: 80,
    },
    title: {
        fontSize: 36,
        fontWeight: "bold",
        textAlign: "center",
        marginBottom: 40,
    },
    infoRow: {
        flexDirection: "row",
        marginBottom: 32,
    },
    infoLeft: {
        flex: 1,
        paddingHorizontal: 16,
        gap: 4,
    },
    infoRight: {
        flex: 1,
        alignItems: "center",
        paddingHorizontal: 16,
    },
    infoText: {
        fontSize: 12,
        marginBottom: 4,
    },
    invoiceText: {
        fontSize: 14,
        fontWeight: "bold",
    },
    table: {
        borderWidth: 1,
        borderColor: "#000000",
        marginVertical: 32,
    },
    tableHeader: {
        flexDirection: "row",
        backgroundColor: "#f59e0b",
    },
    tableRow: {
        flexDirection: "row",
        borderTopWidth: 1,
        borderColor: "#000000",
    },
    tableFooter: {
        flexDirection: "row",
        borderTopWidth: 1,
        borderColor: "#000000",
        backgroundColor: "#f59e0b",
    },
    colQty: {
        width: "10%",
        padding: 8,
        borderRightWidth: 1,
        borderColor: "#000000",
        textAlign: "center",
    },
    colDesc: {
        width: "50%",
        padding: 8,
        borderRightWidth: 1,
        borderColor: "#000000",
    },
    colPrice: {
        width: "20%",
        padding: 8,
        borderRightWidth: 1,
        borderColor: "#000000",
        textAlign: "right",
    },
    colTotal: {
        width: "20%",
        padding: 8,
        textAlign: "right",
    },
    colEmpty: {
        width: "10%",
        borderRightWidth: 1,
        borderColor: "#000000",
    },
    colEmpty2: {
        width: "50%",
        borderRightWidth: 1,
        borderColor: "#000000",
    },
    cellText: {
        fontSize: 11,
    },
    headerText: {
        fontSize: 11,
        fontWeight: "bold",
    },
    subText: {
        fontSize: 9,
        color: "#6b7280",
        marginTop: 2,
    },
    footer: {
        fontSize: 11,
        color: "#ef4444",
        textAlign: "center",
        marginTop: 8,
    },
})

export default function InvoiceMerchPDF({
    name,
    email,
    hash_id,
    invoice_id,
    merch_name,
    merch_price,
    merch_qty,
    merch_size,
    merch_color,
    total_price,
}) {
    const descriptionLines = [merch_name]
    if (merch_size) descriptionLines.push(`Size: ${merch_size}`)
    if (merch_color) descriptionLines.push(`Color: ${merch_color}`)

    return (
        <Document>
            <Page size="A4" style={styles.page}>
                {/* Logo */}
                <View style={styles.logoContainer}>
                    <Image style={styles.logo} src="/assets/images/sh3logo.png" />
                </View>

                {/* Title */}
                <Text style={styles.title}>INVOICE</Text>

                {/* Info Row */}
                <View style={styles.infoRow}>
                    <View style={styles.infoLeft}>
                        <Text style={styles.infoText}>To : {name}</Text>
                        <Text style={styles.infoText}>Email : {email}</Text>
                        <Text style={styles.infoText}>Hash ID : {hash_id}</Text>
                    </View>
                    <View style={styles.infoRight}>
                        <Text style={styles.invoiceText}>Invoice : {invoice_id}</Text>
                    </View>
                </View>

                {/* Table */}
                <View style={styles.table}>
                    {/* Header */}
                    <View style={styles.tableHeader}>
                        <Text style={[styles.colQty, styles.headerText]}>Qty</Text>
                        <Text style={[styles.colDesc, styles.headerText]}>Description</Text>
                        <Text style={[styles.colPrice, styles.headerText]}>Price</Text>
                        <Text style={[styles.colTotal, styles.headerText]}>Total</Text>
                    </View>

                    {/* Row */}
                    <View style={styles.tableRow}>
                        <Text style={[styles.colQty, styles.cellText]}>{merch_qty}</Text>
                        <View style={styles.colDesc}>
                            <Text style={styles.cellText}>{merch_name}</Text>
                            {merch_size && <Text style={styles.subText}>Size: {merch_size}</Text>}
                            {merch_color && <Text style={styles.subText}>Color: {merch_color}</Text>}
                        </View>
                        <Text style={[styles.colPrice, styles.cellText]}>Rp. {merch_price}</Text>
                        <Text style={[styles.colTotal, styles.cellText]}>Rp. {total_price}</Text>
                    </View>

                    {/* Footer */}
                    <View style={styles.tableFooter}>
                        <View style={styles.colEmpty} />
                        <View style={styles.colEmpty2} />
                        <Text style={[styles.colPrice, styles.headerText]}>Total</Text>
                        <Text style={[styles.colTotal, styles.headerText]}>Rp. {total_price}</Text>
                    </View>
                </View>

                {/* Footer Note */}
                <Text style={styles.footer}>
                    Tolong hubungin Admin jika ada pertanyaan terkait pembayaran atau hal yang lain!
                </Text>
            </Page>
        </Document>
    )
}