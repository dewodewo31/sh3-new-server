// components/TotalStatistic.js
export default function TotalStatistic() {
  return (
    <div className="relative overflow-hidden bg-primary-bg text-white">
      {/* Wave Background */}
      <div className="absolute inset-0 w-full h-full pointer-events-none">
        <svg
          width="100%"
          height="100%"
          viewBox="0 0 1440 200"
          xmlns="http://www.w3.org/2000/svg"
          preserveAspectRatio="xMidYMid slice"
        >
          <path
            d="M-100 200 C100 160 250 20 500 40 C700 55 820 140 1000 110 C1150 85 1300 130 1540 160 L1540 185 C1300 158 1150 113 1000 138 C820 168 700 83 500 68 C250 48 100 188 -100 220 Z"
            fill="#fef3c7"
            opacity="0.2"
          />

          <path
            d="M-100 200 C100 155 260 15 510 35 C710 50 830 138 1010 105 C1160 78 1310 125 1540 155 L1540 170 C1310 142 1160 95 1010 120 C830 153 710 68 510 52 C260 32 100 172 -100 215 Z"
            fill="#fcd34d"
            opacity="0.2"
          />

          <path
            d="M-100 200 C100 148 270 8 520 28 C720 44 840 135 1020 100 C1170 72 1320 118 1540 148 L1540 158 C1320 130 1170 84 1020 112 C840 147 720 57 520 41 C270 21 100 161 -100 210 Z"
            fill="#c98520"
            opacity="0.2"
          />

          <path
            d="M-100 200 C100 145 272 5 522 25 C722 41 842 132 1022 97 C1172 69 1322 115 1540 145 L1540 150 C1322 120 1172 74 1022 102 C842 137 722 50 522 34 C272 14 100 152 -100 205 Z"
            fill="#fde68a"
            opacity="0.2"
          />

          <path
            d="M-100 200 C100 142 275 2 525 22 C725 38 845 130 1025 94 C1175 66 1325 112 1540 142 L1540 146 C1325 116 1175 70 1025 98 C845 134 725 46 525 30 C275 10 100 148 -100 203 Z"
            fill="#fef3c7"
            opacity="0.2"
          />

          <path
            d="M-100 200 C100 148 270 8 520 28 C720 44 840 135 1020 100 C1170 72 1320 118 1540 148"
            fill="none"
            stroke="#fde68a"
            strokeWidth="1.5"
            opacity="0.2"
          />

          <path
            d="M-100 210 C100 161 270 21 520 41 C720 57 840 147 1020 112 C1170 84 1320 130 1540 158"
            fill="none"
            stroke="#fcd34d"
            strokeWidth="1"
            opacity="0.2"
          />
        </svg>
      </div>

      {/* Content */}
      <div className="relative z-10 flex flex-col items-center gap-x-8 p-10 md:flex-row md:justify-around">
        <div className="flex flex-col gap-2 items-center">
          <p className="font-semibold text-xl md:text-3xl">2381</p>
          <p className="font-normal text-base md:text-lg">Total Runs</p>
        </div>
        <div className="flex flex-col gap-2 items-center">
          <p className="font-semibold text-xl md:text-3xl">4.200+</p>
          <p className="font-normal text-base md:text-lg">
            Total Member Terdaftar
          </p>
        </div>
        <div className="flex flex-col gap-2 items-center">
          <p className="font-semibold text-xl md:text-3xl">250+</p>
          <p className="font-normal text-base md:text-lg">
            Active Runner Per Week
          </p>
        </div>
        <div className="flex flex-col gap-2 items-center">
          <p className="font-semibold text-xl md:text-3xl">ON ON</p>
          <p className="font-normal text-base md:text-lg">Per Week</p>
        </div>
      </div>
    </div>
  );
}
