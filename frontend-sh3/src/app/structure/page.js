"use client";
import Container from "@/src/components/Container";
import { RevealSection } from "@/src/components/RevealSection";
import TotalStatistic from "@/src/components/TotalStatistics";
import Image from "next/image";
import StructureProfileCard from "@/src/components/StructureProfileCard";
import BatikOverlay from "@/src/components/BatikOverlay";
import { useState, useEffect } from "react";
import { organisationService } from "@/src/services/organisationService";

export default function About() {
  const [tree, setTree] = useState([]);
  const [years, setYears] = useState([]);
  const [selectedYear, setSelectedYear] = useState(new Date().getFullYear());
  const [loading, setLoading] = useState(true);

  function OrgNode({ node }) {
    const hasChildren = node.children && node.children.length > 0;

    return (
      <div className="flex flex-col items-center">
        {/* Card jabatan */}
        <div className="flex flex-wrap justify-center gap-3">
          {node.holders?.length > 0 ? (
            node.holders.map((holder, i) => (
              <div
                key={i}
                className="flex flex-col items-center bg-primary-light border-2 border-neutral-normal p-4 min-w-40 max-w-48 text-center"
              >
                <div className="font-bold font-young text-sm text-neutral-normal leading-tight">
                  {node.position_name}
                </div>
                <div className="text-xs text-neutral-dark mt-1 font-medium">
                  {holder.name}
                </div>
                {holder.period_text && (
                  <div className="text-xs text-neutral-dark mt-1 opacity-60">
                    {holder.period_text}
                  </div>
                )}
              </div>
            ))
          ) : (
            <div className="flex flex-col items-center bg-primary-light border-2 border-neutral-normal p-4 min-w-40 max-w-48 text-center">
              <div className="font-bold font-young text-sm text-neutral-normal">
                {node.position_name}
              </div>
              <div className="text-xs text-neutral-dark mt-1 italic">Kosong</div>
            </div>
          )}
        </div>

        {/* Garis penghubung ke children */}
        {hasChildren && (
          <>
            <div className="w-0.5 h-6 bg-neutral-normal" />
            <div className="flex flex-row items-start gap-4 md:gap-8">
              {node.children.map((child, i) => (
                <div key={i} className="flex flex-col items-center">
                  {/* Garis horizontal */}
                  {node.children.length > 1 && (
                    <div className="w-full h-0.5 bg-neutral-normal" />
                  )}
                  <div className="w-0.5 h-6 bg-neutral-normal" />
                  <OrgNode node={child} />
                </div>
              ))}
            </div>
          </>
        )}
      </div>
    );
  }

  function OrgTree({ nodes }) {
    return (
      <div className="overflow-x-auto pb-8">
        <div className="flex flex-col items-center min-w-max mx-auto">
          {nodes.map((node, i) => (
            <OrgNode key={i} node={node} />
          ))}
        </div>
      </div>
    );
  }

  useEffect(() => {
    // Ambil available years
    organisationService
      .getYears()
      .then((res) => setYears(res.data.data))
      .catch((err) => console.error(err));
  }, []);

  useEffect(() => {
    setLoading(true);
    organisationService
      .getTree(selectedYear)
      .then((res) => {
        setTree(res.data.tree);
      })
      .catch((err) => console.error("Error:", err)) // ← tambah ini
      .finally(() => setLoading(false));
  }, [selectedYear]);

  return (
    <Container className="flex flex-col">
      <div className="relative bg-linear-to-b from-primary-light to-primary-light-hover">
        <BatikOverlay />

        {/* Header */}
        <div className="flex flex-col flex-1 items-center justify-center text-primary-dark-active p-8 mt-16">
          <h1 className="text-5xl font-bold font-young">Struktur Organisasi</h1>
        </div>

        {/* Struktur Organisasi dari API */}
        {/* Struktur Organisasi dari API */}
        <RevealSection direction="up">
          <div className="p-8">
            <div className="flex flex-col max-w-306 mx-auto w-full gap-8">
              <div className="flex flex-col md:flex-row justify-between items-center">
                <div className="font-bold text-4xl font-young md:p-4 text-primary-dark-active">
                  Struktur Tahun
                </div>
                {years.length > 0 && (
                  <select
                    value={selectedYear}
                    onChange={(e) => setSelectedYear(e.target.value)}
                    className="border-2 border-neutral-normal bg-primary-light px-4 py-2 font-young text-lg"
                  >
                    {years.map((year) => (
                      <option key={year} value={year}>{year}</option>
                    ))}
                  </select>
                )}
              </div>

              {loading ? (
                <div className="flex justify-center p-8 text-xl">Loading...</div>
              ) : tree.length === 0 ? (
                <div className="flex justify-center p-8 text-xl text-neutral-dark h-screen">
                  Tidak ada data struktur organisasi.
                </div>
              ) : (
                <OrgTree nodes={tree} />
              )}
            </div>
          </div>
        </RevealSection>
      </div>
    </Container>
  );
}
